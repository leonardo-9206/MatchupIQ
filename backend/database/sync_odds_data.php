<?php
/**
 * CLI Script: Sync Odds & Update NFL Calendar Games
 * Usage:
 *   php backend/database/sync_odds_data.php             (Uses cache / dev-safe)
 *   php backend/database/sync_odds_data.php --refresh   (Forces fresh API fetch)
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/OddsApiService.php';

echo "====================================================\n";
echo " MatchupIQ — NFL Calendar & Odds Data Sync Runner\n";
echo "====================================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$options = getopt('', ['refresh']);
$forceRefresh = isset($options['refresh']);

try {
    $db = Database::getInstance()->getConnection();
    $oddsService = new OddsApiService($db);

    echo "[*] Step 1: Checking odds cache and retrieving NFL market events...\n";

    // Check if odds_cache has active or stale entries
    $cacheStmt = $db->query("SELECT `cache_key`, `sport_key`, `response_data`, `expires_at` FROM `odds_cache` ORDER BY `fetched_at` DESC LIMIT 1");
    $cachedRow = $cacheStmt->fetch(PDO::FETCH_ASSOC);

    $events = [];
    $source = 'cache';

    if (!$forceRefresh && $cachedRow && !empty($cachedRow['response_data'])) {
        $events = json_decode($cachedRow['response_data'], true);
        echo "    [+] Retrieved " . count($events) . " events from existing `odds_cache`.\n";
    } else {
        // Fetch via OddsApiService if API key is present or forced
        $sport = 'americanfootball_nfl';
        $markets = ['h2h', 'player_pass_yds', 'player_rush_yds', 'player_reception_yds'];

        $apiResult = $oddsService->getOdds($sport, $markets, $forceRefresh);
        if ($apiResult['success']) {
            $events = $apiResult['data'] ?? [];
            $source = $apiResult['source'];
            echo "    [+] Fetched " . count($events) . " events via {$source}.\n";
        } else {
            echo "    [!] Warning: External odds API fetch was unsuccessful: " . ($apiResult['error'] ?? 'Unknown error') . "\n";
            if ($cachedRow && !empty($cachedRow['response_data'])) {
                $events = json_decode($cachedRow['response_data'], true);
                echo "    [*] Falling back to cached events (" . count($events) . " events).\n";
            }
        }
    }

    // Step 2: Ensure upcoming matches are in `games` table
    echo "\n[*] Step 2: Synchronizing NFL calendar matches into `games` table...\n";
    $gamesCreated = 0;
    $gamesExisting = 0;

    $teamLookup = $db->prepare("SELECT team_id, name, abbreviation, defensive_rank FROM teams WHERE LOWER(name) = LOWER(:tname) OR LOWER(abbreviation) = LOWER(:tabbr) LIMIT 1");
    $checkGame = $db->prepare("SELECT game_id FROM games WHERE (home_team_id = :hid1 AND away_team_id = :aid1) OR (home_team_id = :aid2 AND away_team_id = :hid2) LIMIT 1");
    $insertGame = $db->prepare("INSERT INTO games (sport_id, home_team_id, away_team_id, game_date) VALUES (1, :hid, :aid, :gdate)");

    foreach ($events as $ev) {
        $home = trim($ev['home_team'] ?? '');
        $away = trim($ev['away_team'] ?? '');
        if ($home === '' || $away === '') continue;

        $teamLookup->execute([':tname' => $home, ':tabbr' => $home]);
        $homeRow = $teamLookup->fetch();

        $teamLookup->execute([':tname' => $away, ':tabbr' => $away]);
        $awayRow = $teamLookup->fetch();

        if ($homeRow && $awayRow) {
            $hid = (int) $homeRow['team_id'];
            $aid = (int) $awayRow['team_id'];

            $checkGame->execute([':hid1' => $hid, ':aid1' => $aid, ':aid2' => $aid, ':hid2' => $hid]);
            $existingGame = $checkGame->fetch();

            if (!$existingGame) {
                $rawDate = $ev['commence_time'] ?? '';
                $gDate = strlen($rawDate) >= 19
                    ? str_replace(['T', 'Z'], [' ', ''], substr($rawDate, 0, 19))
                    : date('Y-m-d H:i:s', time() + 86400);

                $insertGame->execute([
                    ':hid'   => $hid,
                    ':aid'   => $aid,
                    ':gdate' => $gDate
                ]);
                $gamesCreated++;
            } else {
                $gamesExisting++;
            }
        }
    }

    echo "    [+] Matches Verified: {$gamesExisting} existing, {$gamesCreated} newly created in calendar.\n";

    // Step 3: Trigger props sync & Value Edge recalculation against opposing defensive rankings
    echo "\n[*] Step 3: Recalculating props and Value Edge against real weekly opponents...\n";
    $syncStats = $oddsService->syncOddsToProps($events);

    echo "    [+] Events Processed: " . ($syncStats['events_processed'] ?? 0) . "\n";
    echo "    [+] Props Updated:    " . ($syncStats['updated'] ?? 0) . "\n";
    echo "    [+] Props Inserted:   " . ($syncStats['inserted'] ?? 0) . "\n";

    // Step 4: Display current calendar overview
    echo "\n--- Current Active Calendar Games in MatchupIQ ---\n";
    $gamesSql = "SELECT g.game_id, g.game_date, 
                        home_t.name AS home_team, home_t.abbreviation AS home_abbr, home_t.defensive_rank AS home_rank,
                        away_t.name AS away_team, away_t.abbreviation AS away_abbr, away_t.defensive_rank AS away_rank
                 FROM games g
                 INNER JOIN teams home_t ON g.home_team_id = home_t.team_id
                 INNER JOIN teams away_t ON g.away_team_id = away_t.team_id
                 ORDER BY g.game_date ASC";
    $games = $db->query($gamesSql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($games as $g) {
        echo sprintf(
            "  [Game #%d] %s vs %s (%s vs %s) — %s | Ranks: #%d vs #%d\n",
            $g['game_id'],
            $g['home_abbr'],
            $g['away_abbr'],
            $g['home_team'],
            $g['away_team'],
            substr($g['game_date'], 0, 16),
            $g['home_rank'],
            $g['away_rank']
        );
    }

    echo "\n[+] NFL calendar & odds synchronization complete!\n";
    exit(0);

} catch (Throwable $e) {
    echo "\n[!] Sync Error: " . $e->getMessage() . "\n";
    exit(1);
}

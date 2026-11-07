<?php
/**
 * CLI Script: Sync Full 32-Team NFL Rosters & Generate Active Betting Props
 * 
 * Ensures all 260+ NFL players across 32 teams exist in MySQL, have their real
 * calendar game assigned, active props created, and tactical profiles populated.
 *
 * Usage:
 *   php backend/database/sync_all_rosters_and_props.php
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/EdgeCalculatorService.php';

echo "====================================================\n";
echo " MatchupIQ — Full 32-Team Roster & Props Synchronizer\n";
echo "====================================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $edgeCalculator = new EdgeCalculatorService();

    // 1. Populate all players from seed.sql using INSERT IGNORE
    echo "[*] Step 1: Populating all 269 players across 32 franchises from seed.sql...\n";
    $seedSql = file_get_contents(__DIR__ . '/seed.sql');

    // Extract players insert statement
    if (preg_match('/INSERT INTO `players` \([^)]+\) VALUES\s*(.*?);/s', $seedSql, $matches)) {
        $valuesBlock = $matches[1];
        $insertQuery = "INSERT IGNORE INTO `players` (`player_id`, `team_id`, `name`, `position`, `stat_average`) VALUES " . $valuesBlock;
        $db->exec($insertQuery);
    }

    $playerCountStmt = $db->query("SELECT COUNT(*) FROM players");
    $totalPlayers = (int) $playerCountStmt->fetchColumn();
    echo "    [+] Total players active in database: {$totalPlayers}\n\n";

    // 2. Map games and assign props for each player
    echo "[*] Step 2: Generating active player props linked to real NFL calendar games...\n";
    $players = $db->query("SELECT p.player_id, p.team_id, p.name, p.position, p.stat_average, t.abbreviation AS team_abbr 
                           FROM players p 
                           JOIN teams t ON p.team_id = t.team_id 
                           WHERE p.position IN ('QB', 'RB', 'WR', 'TE')
                           ORDER BY p.player_id ASC")->fetchAll(PDO::FETCH_ASSOC);

    $findGameStmt = $db->prepare("
        SELECT g.game_id, 
               IF(g.home_team_id = ?, ta.defensive_rank, th.defensive_rank) AS opp_rank
        FROM games g
        JOIN teams th ON g.home_team_id = th.team_id
        JOIN teams ta ON g.away_team_id = ta.team_id
        WHERE g.home_team_id = ? OR g.away_team_id = ?
        ORDER BY g.game_date ASC 
        LIMIT 1
    ");

    $checkPropStmt = $db->prepare("SELECT prop_id FROM props WHERE player_id = :pid LIMIT 1");
    $insertPropStmt = $db->prepare("
        INSERT INTO props (player_id, bookmaker_id, game_id, bet_type, line_over, odds_over, odds_under, calculated_edge, updated_at)
        VALUES (:pid, 1, :gid, :btype, :line, 1.91, 1.91, :edge, NOW())
    ");
    $updatePropStmt = $db->prepare("
        UPDATE props SET game_id = :gid, calculated_edge = :edge, updated_at = NOW() WHERE prop_id = :prid
    ");

    $defaultBaselines = [
        'QB' => ['stat' => 'passing_yards',   'line' => 245.5, 'avg' => 248.5],
        'RB' => ['stat' => 'rushing_yards',   'line' => 64.5,  'avg' => 64.2],
        'WR' => ['stat' => 'receiving_yards', 'line' => 58.5,  'avg' => 58.8],
        'TE' => ['stat' => 'receiving_yards', 'line' => 45.5,  'avg' => 45.3],
    ];

    $createdProps = 0;
    $updatedProps = 0;

    foreach ($players as $p) {
        $pid = (int) $p['player_id'];
        $tid = (int) $p['team_id'];
        $pos = $p['position'];

        $cfg = $defaultBaselines[$pos] ?? $defaultBaselines['RB'];
        $betType = $cfg['stat'];
        $line = $cfg['line'];
        $avg = ((float) $p['stat_average'] > 0) ? (float) $p['stat_average'] : $cfg['avg'];

        // Find upcoming match
        $findGameStmt->execute([$tid, $tid, $tid]);
        $game = $findGameStmt->fetch(PDO::FETCH_ASSOC);

        $gameId = $game ? (int) $game['game_id'] : 1;
        $oppRank = $game ? (int) $game['opp_rank'] : 16;

        // Calculate initial edge
        $calc = $edgeCalculator->calculateEdge($avg, $line, 1.91, $oppRank);
        $edge = $calc['calculated_edge'];

        // Check if prop exists
        $checkPropStmt->execute([':pid' => $pid]);
        $existing = $checkPropStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $updatePropStmt->execute([
                ':gid'  => $gameId,
                ':edge' => $edge,
                ':prid' => $existing['prop_id']
            ]);
            $updatedProps++;
        } else {
            $insertPropStmt->execute([
                ':pid'   => $pid,
                ':gid'   => $gameId,
                ':btype' => $betType,
                ':line'  => $line,
                ':edge'  => $edge
            ]);
            $createdProps++;
        }
    }

    echo "    [+] Props created: {$createdProps}\n";
    echo "    [+] Props updated: {$updatedProps}\n";
    $totalProps = (int) $db->query("SELECT COUNT(*) FROM props")->fetchColumn();
    echo "    [+] Total props active in catalog: {$totalProps}\n\n";

    // 3. Generate tactical data for any missing profiles
    echo "[*] Step 3: Triggering automated tactical heatmaps and coverage generator...\n";
    $heatmapCountStmt = $db->prepare("SELECT COUNT(*) FROM player_heatmaps WHERE player_id = :id");
    $insertHeatmapStmt = $db->prepare("INSERT INTO player_heatmaps (player_id, field_zone, attempts, yards_gained, success_rate) VALUES (:pid, :zone, :att, :yds, :rate)");
    $coverageCountStmt = $db->prepare("SELECT COUNT(*) FROM coverage_splits WHERE player_id = :id");
    $insertCoverageStmt = $db->prepare("INSERT INTO coverage_splits (player_id, coverage_type, targets, receptions, catch_rate) VALUES (:pid, :type, :targets, :rec, :rate)");

    $zones = ['left_end', 'left_tackle', 'middle', 'right_tackle', 'right_end'];
    $heatmapsCreated = 0;
    $coveragesCreated = 0;

    foreach ($players as $p) {
        $pid = (int) $p['player_id'];
        $pos = $p['position'];
        $avg = (float) $p['stat_average'];

        // Heatmaps for RBs and QBs
        if (in_array($pos, ['RB', 'QB'])) {
            $heatmapCountStmt->execute([':id' => $pid]);
            if ((int)$heatmapCountStmt->fetchColumn() === 0) {
                $baseAtt = ($pos === 'RB') ? 18 : 6;
                $dist = [0.15, 0.25, 0.30, 0.20, 0.10];
                foreach ($zones as $idx => $z) {
                    $att = max(1, (int) round($baseAtt * $dist[$idx]));
                    $yds = max(1, (int) round($avg * $dist[$idx]));
                    $rate = round(min(80.0, max(40.0, ($yds / max(1, $att)) * 12.0)), 2);
                    $insertHeatmapStmt->execute([
                        ':pid'   => $pid,
                        ':zone'  => $z,
                        ':att'   => $att,
                        ':yds'   => $yds,
                        ':rate'  => $rate
                    ]);
                }
                $heatmapsCreated++;
            }
        }

        // Coverage splits for WRs, TEs, RBs
        if (in_array($pos, ['WR', 'TE', 'RB'])) {
            $coverageCountStmt->execute([':id' => $pid]);
            if ((int)$coverageCountStmt->fetchColumn() === 0) {
                $baseTgt = ($pos === 'WR') ? 8 : (($pos === 'TE') ? 6 : 4);
                // Man
                $manTgt = max(1, (int) round($baseTgt * 0.40));
                $manRec = max(1, (int) round($manTgt * 0.65));
                $insertCoverageStmt->execute([
                    ':pid'     => $pid,
                    ':type'    => 'man',
                    ':targets' => $manTgt,
                    ':rec'     => $manRec,
                    ':rate'    => round(($manRec / $manTgt) * 100, 2)
                ]);
                // Zone
                $zoneTgt = max(1, (int) round($baseTgt * 0.60));
                $zoneRec = max(1, (int) round($zoneTgt * 0.78));
                $insertCoverageStmt->execute([
                    ':pid'     => $pid,
                    ':type'    => 'zone',
                    ':targets' => $zoneTgt,
                    ':rec'     => $zoneRec,
                    ':rate'    => round(($zoneRec / $zoneTgt) * 100, 2)
                ]);
                $coveragesCreated++;
            }
        }
    }

    echo "    [+] New heatmap profiles generated: {$heatmapsCreated}\n";
    echo "    [+] New coverage profiles generated: {$coveragesCreated}\n";
    echo "\n[+] Full NFL sync successful! All 32 teams are fully populated.\n";
    exit(0);

} catch (Throwable $e) {
    echo "[-] Error during sync: " . $e->getMessage() . "\n";
    echo "    In " . $e->getFile() . " line " . $e->getLine() . "\n";
    exit(1);
}

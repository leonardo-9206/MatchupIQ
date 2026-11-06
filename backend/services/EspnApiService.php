<?php
/**
 * EspnApiService
 * Consumes ESPN's public REST APIs via cURL to ingest real player profiles and recent game logs.
 * Populates `player_game_logs` with historical match performance (last 5 games) and keeps
 * `players.stat_average` aligned with empirical athlete trends.
 */

require_once __DIR__ . '/../config/Database.php';

class EspnApiService
{
    private PDO $db;
    private string $commonBaseUrl;
    private string $siteBaseUrl;

    /**
     * Preset catalog of prominent NFL athletes mapped to their ESPN IDs and primary stat categories.
     */
    private array $presetNflAthletes = [
        [
            'espn_id'   => 3139477,
            'name'      => 'Patrick Mahomes',
            'position'  => 'QB',
            'team_abbr' => 'KC',
            'stat_type' => 'passing_yards',
            'espn_stat' => 'passingYards',
            'line'      => 268.5
        ],
        [
            'espn_id'   => 15847,
            'name'      => 'Travis Kelce',
            'position'  => 'TE',
            'team_abbr' => 'KC',
            'stat_type' => 'receiving_yards',
            'espn_stat' => 'receivingYards',
            'line'      => 68.5
        ],
        [
            'espn_id'   => 4361529,
            'name'      => 'Isiah Pacheco',
            'position'  => 'RB',
            'team_abbr' => 'KC',
            'stat_type' => 'rushing_yards',
            'espn_stat' => 'rushingYards',
            'line'      => 65.5
        ],
        [
            'espn_id'   => 3117251,
            'name'      => 'Christian McCaffrey',
            'position'  => 'RB',
            'team_abbr' => 'SF',
            'stat_type' => 'rushing_yards',
            'espn_stat' => 'rushingYards',
            'line'      => 82.5
        ],
        [
            'espn_id'   => 4361741,
            'name'      => 'Brock Purdy',
            'position'  => 'QB',
            'team_abbr' => 'SF',
            'stat_type' => 'passing_yards',
            'espn_stat' => 'passingYards',
            'line'      => 245.5
        ],
        [
            'espn_id'   => 3040151,
            'name'      => 'George Kittle',
            'position'  => 'TE',
            'team_abbr' => 'SF',
            'stat_type' => 'receiving_yards',
            'espn_stat' => 'receivingYards',
            'line'      => 58.5
        ],
        [
            'espn_id'   => 3916387,
            'name'      => 'Lamar Jackson',
            'position'  => 'QB',
            'team_abbr' => 'BAL',
            'stat_type' => 'passing_yards',
            'espn_stat' => 'passingYards',
            'line'      => 232.5
        ],
        [
            'espn_id'   => 3043078,
            'name'      => 'Derrick Henry',
            'position'  => 'RB',
            'team_abbr' => 'BAL',
            'stat_type' => 'rushing_yards',
            'espn_stat' => 'rushingYards',
            'line'      => 85.5
        ],
        [
            'espn_id'   => 3918298,
            'name'      => 'Josh Allen',
            'position'  => 'QB',
            'team_abbr' => 'BUF',
            'stat_type' => 'passing_yards',
            'espn_stat' => 'passingYards',
            'line'      => 255.5
        ],
        [
            'espn_id'   => 4379399,
            'name'      => 'James Cook',
            'position'  => 'RB',
            'team_abbr' => 'BUF',
            'stat_type' => 'rushing_yards',
            'espn_stat' => 'rushingYards',
            'line'      => 62.5
        ]
    ];

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
        $this->commonBaseUrl = 'https://site.web.api.espn.com/apis/common/v3/sports';
        $this->siteBaseUrl = 'https://site.api.espn.com/apis/site/v2/sports';
    }

    /**
     * Fetches raw gamelog JSON from ESPN for a specific athlete.
     *
     * @param int $espnAthleteId Athlete ID from ESPN
     * @param string $sportPath Path identifier (e.g. 'football/nfl', 'soccer/mex.1')
     * @return array|null Parsed JSON response or null on network error
     */
    public function fetchGamelog(int $espnAthleteId, string $sportPath = 'football/nfl'): ?array
    {
        $url = "{$this->commonBaseUrl}/{$sportPath}/athletes/{$espnAthleteId}/gamelog";
        $response = $this->executeCurl($url);

        if (!$response['success'] || !is_array($response['data'])) {
            return null;
        }

        return $response['data'];
    }

    /**
     * Parses ESPN gamelog structure into standardized database records for a target stat.
     *
     * @param array $gamelog Raw ESPN gamelog response
     * @param string $espnStatTarget Metric key in ESPN names array (e.g. 'passingYards')
     * @param string $dbStatType Standardized database stat identifier (e.g. 'passing_yards')
     * @param int $limit Maximum number of most recent games to extract (default 5)
     * @return array Standardized log entries
     */
    public function parseRecentLogs(
        array $gamelog,
        string $espnStatTarget,
        string $dbStatType,
        int $limit = 5
    ): array {
        $names = $gamelog['names'] ?? [];
        $eventsMap = $gamelog['events'] ?? [];

        $statIndex = array_search($espnStatTarget, $names, true);
        if ($statIndex === false) {
            // Fallback for rushing/receiving variations
            $statIndex = $this->findFallbackStatIndex($names, $espnStatTarget);
            if ($statIndex === false) {
                return [];
            }
        }

        $logs = [];

        if (isset($gamelog['seasonTypes']) && is_array($gamelog['seasonTypes'])) {
            foreach ($gamelog['seasonTypes'] as $seasonType) {
                $categories = $seasonType['categories'] ?? [];
                foreach ($categories as $cat) {
                    $events = $cat['events'] ?? [];
                    foreach ($events as $ev) {
                        $evId = (string) ($ev['eventId'] ?? '');
                        $meta = $eventsMap[$evId] ?? null;
                        if (!$meta) {
                            continue;
                        }

                        $stats = $ev['stats'] ?? [];
                        if (!isset($stats[$statIndex]) || !is_numeric($stats[$statIndex])) {
                            continue;
                        }

                        $statValue = (float) $stats[$statIndex];
                        $dateRaw = $meta['gameDate'] ?? '';
                        $date = strlen($dateRaw) >= 10 ? substr($dateRaw, 0, 10) : date('Y-m-d');
                        $oppAbbr = strtoupper(trim($meta['opponent']['abbreviation'] ?? 'OPP'));
                        $isHome = ($meta['atVs'] ?? '') !== '@' ? 1 : 0;

                        $logs[] = [
                            'event_id'      => $evId,
                            'opponent_abbr' => $oppAbbr,
                            'stat_type'     => $dbStatType,
                            'stat_value'    => $statValue,
                            'game_date'     => $date,
                            'is_home'       => $isHome
                        ];
                    }
                }
            }
        }

        // Sort by date descending and slice the top N games
        usort($logs, fn($a, $b) => strcmp($b['game_date'], $a['game_date']));
        return array_slice($logs, 0, $limit);
    }

    /**
     * Persists game logs into MySQL `player_game_logs` table for a specific player ID.
     * Updates player stat_average with the empirical 5-game average.
     *
     * @param int $playerId Foreign key referencing `players`
     * @param array $logs Array of parsed game logs
     * @return int Count of logs stored/updated
     */
    public function ingestLogsForPlayer(int $playerId, array $logs): int
    {
        if (empty($logs)) {
            return 0;
        }

        $stored = 0;
        $totalStat = 0.0;
        $statCount = 0;

        foreach ($logs as $log) {
            $checkSql = "SELECT log_id FROM player_game_logs 
                         WHERE player_id = :player_id 
                           AND stat_type = :stat_type 
                           AND game_date = :game_date 
                         LIMIT 1";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([
                ':player_id' => $playerId,
                ':stat_type' => $log['stat_type'],
                ':game_date' => $log['game_date']
            ]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                $updateSql = "UPDATE player_game_logs SET 
                                  opponent_abbr = :opponent_abbr,
                                  stat_value    = :stat_value,
                                  is_home       = :is_home
                              WHERE log_id = :log_id";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([
                    ':opponent_abbr' => $log['opponent_abbr'],
                    ':stat_value'    => $log['stat_value'],
                    ':is_home'       => $log['is_home'],
                    ':log_id'        => (int) $existing['log_id']
                ]);
            } else {
                $insertSql = "INSERT INTO player_game_logs 
                                  (player_id, opponent_abbr, stat_type, stat_value, game_date, is_home)
                              VALUES 
                                  (:player_id, :opponent_abbr, :stat_type, :stat_value, :game_date, :is_home)";
                $insertStmt = $this->db->prepare($insertSql);
                $insertStmt->execute([
                    ':player_id'     => $playerId,
                    ':opponent_abbr' => $log['opponent_abbr'],
                    ':stat_type'     => $log['stat_type'],
                    ':stat_value'    => $log['stat_value'],
                    ':game_date'     => $log['game_date'],
                    ':is_home'       => $log['is_home']
                ]);
            }

            $stored++;
            $totalStat += (float) $log['stat_value'];
            $statCount++;
        }

        // Align players.stat_average with recent empirical trend
        if ($statCount > 0) {
            $newAverage = round($totalStat / $statCount, 2);
            $updatePlayer = $this->db->prepare("UPDATE players SET stat_average = :avg WHERE player_id = :id");
            $updatePlayer->execute([':avg' => $newAverage, ':id' => $playerId]);
        }

        return $stored;
    }

    /**
     * Executes end-to-end synchronization for all preset athletes:
     * 1. Ensures player records exist in MySQL.
     * 2. Fetches real ESPN gamelogs via cURL.
     * 3. Parses the last 5 games.
     * 4. Persists rows in `player_game_logs`.
     * 5. Ensures matching `props` exist so Antonio's frontend immediately renders them.
     *
     * @param int $limit Number of recent games per player (default 5)
     * @return array Ingestion summary report
     */
    public function syncAllPresetAthletes(int $limit = 5): array
    {
        $athletesSynced = 0;
        $totalLogsIngested = 0;
        $playersCreated = 0;
        $propsCreated = 0;
        $details = [];

        foreach ($this->presetNflAthletes as $athlete) {
            $playerId = $this->resolveOrInsertPlayer($athlete, $playersCreated);
            if (!$playerId) {
                continue;
            }

            // Fetch live gamelog from ESPN
            $gamelogData = $this->fetchGamelog($athlete['espn_id'], 'football/nfl');
            $logs = [];

            if ($gamelogData !== null) {
                $logs = $this->parseRecentLogs(
                    $gamelogData,
                    $athlete['espn_stat'],
                    $athlete['stat_type'],
                    $limit
                );
            }

            // Ingestion
            if (!empty($logs)) {
                $ingested = $this->ingestLogsForPlayer($playerId, $logs);
                $totalLogsIngested += $ingested;
                $athletesSynced++;

                $details[] = [
                    'player'     => $athlete['name'],
                    'position'   => $athlete['position'],
                    'team'       => $athlete['team_abbr'],
                    'stat_type'  => $athlete['stat_type'],
                    'logs_count' => count($logs),
                    'source'     => 'espn_api'
                ];
            }

            // Ensure player has a prop line in the catalog for frontend display
            $this->ensurePropExistsForPlayer($playerId, $athlete, $propsCreated);
        }

        return [
            'success'             => true,
            'athletes_processed'  => count($this->presetNflAthletes),
            'athletes_synced'     => $athletesSynced,
            'total_logs_ingested' => $totalLogsIngested,
            'players_created'     => $playersCreated,
            'props_created'       => $propsCreated,
            'details'             => $details
        ];
    }

    /**
     * Resolves player_id from MySQL or registers athlete under the correct team.
     */
    private function resolveOrInsertPlayer(array $athlete, int &$playersCreated): ?int
    {
        $stmt = $this->db->prepare("SELECT player_id FROM players WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $stmt->execute([':name' => $athlete['name']]);
        $row = $stmt->fetch();

        if ($row) {
            return (int) $row['player_id'];
        }

        // Find team_id by abbreviation
        $teamStmt = $this->db->prepare("SELECT team_id FROM teams WHERE LOWER(abbreviation) = LOWER(:abbr) LIMIT 1");
        $teamStmt->execute([':abbr' => $athlete['team_abbr']]);
        $teamRow = $teamStmt->fetch();

        if (!$teamRow) {
            return null;
        }

        $teamId = (int) $teamRow['team_id'];
        $defaultAvg = (float) ($athlete['line'] ?? 50.0);

        $insert = $this->db->prepare("INSERT INTO players (team_id, name, position, stat_average) VALUES (:team_id, :name, :pos, :avg)");
        $insert->execute([
            ':team_id' => $teamId,
            ':name'    => $athlete['name'],
            ':pos'     => $athlete['position'],
            ':avg'     => $defaultAvg
        ]);

        $playersCreated++;
        return (int) $this->db->lastInsertId();
    }

    /**
     * Verifies that the athlete has at least one active prop record in MySQL
     * so that the frontend immediately renders their card.
     */
    private function ensurePropExistsForPlayer(int $playerId, array $athlete, int &$propsCreated): void
    {
        $check = $this->db->prepare("SELECT prop_id FROM props WHERE player_id = :pid LIMIT 1");
        $check->execute([':pid' => $playerId]);
        if ($check->fetch()) {
            return;
        }

        // Find active game for this player's team
        $gameStmt = $this->db->prepare("SELECT g.game_id 
            FROM games g 
            INNER JOIN players p ON (g.home_team_id = p.team_id OR g.away_team_id = p.team_id)
            WHERE p.player_id = :pid 
            LIMIT 1");
        $gameStmt->execute([':pid' => $playerId]);
        $gameRow = $gameStmt->fetch();

        $gameId = $gameRow ? (int) $gameRow['game_id'] : 1;
        $line = (float) ($athlete['line'] ?? 50.0);
        $betType = $athlete['stat_type'];

        $insert = $this->db->prepare("INSERT INTO props 
            (player_id, bookmaker_id, game_id, bet_type, line_over, odds_over, odds_under, calculated_edge, updated_at)
            VALUES 
            (:pid, 1, :gid, :btype, :line, 1.91, 1.91, 8.50, NOW())");
        $insert->execute([
            ':pid'   => $playerId,
            ':gid'   => $gameId,
            ':btype' => $betType,
            ':line'  => $line
        ]);

        $propsCreated++;
    }

    /**
     * Fallback helper for resolving stat index when name casing or terminology varies.
     */
    private function findFallbackStatIndex(array $names, string $target): false|int
    {
        $lowTarget = strtolower($target);
        foreach ($names as $idx => $name) {
            if (strtolower($name) === $lowTarget || str_contains(strtolower($name), $lowTarget)) {
                return $idx;
            }
        }
        return false;
    }

    /**
     * Executes standard HTTP GET cURL request with timeout and browser agent.
     */
    private function executeCurl(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) MatchupIQ-Ingestion/1.0',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json'
            ]
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            return [
                'success' => false,
                'error'   => "cURL network error: {$curlError}"
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success'   => false,
                'http_code' => $httpCode,
                'error'     => "ESPN API responded with HTTP {$httpCode}"
            ];
        }

        $decoded = json_decode($body, true);
        if ($decoded === null) {
            return [
                'success' => false,
                'error'   => 'Failed to parse JSON response from ESPN API.'
            ];
        }

        return [
            'success' => true,
            'data'    => $decoded
        ];
    }
}

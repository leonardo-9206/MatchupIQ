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
        ],
        [
            'espn_id'   => 3915511,
            'name'      => 'Joe Burrow',
            'position'  => 'QB',
            'team_abbr' => 'CIN',
            'stat_type' => 'passing_yards',
            'espn_stat' => 'passingYards',
            'line'      => 268.5
        ],
        [
            'espn_id'   => 4362628,
            'name'      => "Ja'Marr Chase",
            'position'  => 'WR',
            'team_abbr' => 'CIN',
            'stat_type' => 'receiving_yards',
            'espn_stat' => 'receivingYards',
            'line'      => 84.5
        ],
        [
            'espn_id'   => 4432577,
            'name'      => 'C.J. Stroud',
            'position'  => 'QB',
            'team_abbr' => 'HOU',
            'stat_type' => 'passing_yards',
            'espn_stat' => 'passingYards',
            'line'      => 258.5
        ],
        [
            'espn_id'   => 4258173,
            'name'      => 'Nico Collins',
            'position'  => 'WR',
            'team_abbr' => 'HOU',
            'stat_type' => 'receiving_yards',
            'espn_stat' => 'receivingYards',
            'line'      => 78.5
        ],
        [
            'espn_id'   => 4262921,
            'name'      => 'Justin Jefferson',
            'position'  => 'WR',
            'team_abbr' => 'MIN',
            'stat_type' => 'receiving_yards',
            'espn_stat' => 'receivingYards',
            'line'      => 88.5
        ],
        [
            'espn_id'   => 4241389,
            'name'      => 'CeeDee Lamb',
            'position'  => 'WR',
            'team_abbr' => 'DAL',
            'stat_type' => 'receiving_yards',
            'espn_stat' => 'receivingYards',
            'line'      => 85.5
        ],
        [
            'espn_id'   => 4374302,
            'name'      => 'Amon-Ra St. Brown',
            'position'  => 'WR',
            'team_abbr' => 'DET',
            'stat_type' => 'receiving_yards',
            'espn_stat' => 'receivingYards',
            'line'      => 79.5
        ],
        [
            'espn_id'   => 4430755,
            'name'      => 'Jahmyr Gibbs',
            'position'  => 'RB',
            'team_abbr' => 'DET',
            'stat_type' => 'rushing_yards',
            'espn_stat' => 'rushingYards',
            'line'      => 68.5
        ],
        [
            'espn_id'   => 3929630,
            'name'      => 'Saquon Barkley',
            'position'  => 'RB',
            'team_abbr' => 'PHI',
            'stat_type' => 'rushing_yards',
            'espn_stat' => 'rushingYards',
            'line'      => 82.5
        ],
        [
            'espn_id'   => 4040715,
            'name'      => 'Jalen Hurts',
            'position'  => 'QB',
            'team_abbr' => 'PHI',
            'stat_type' => 'passing_yards',
            'espn_stat' => 'passingYards',
            'line'      => 235.5
        ],
        [
            'espn_id'   => 4427366,
            'name'      => 'Breece Hall',
            'position'  => 'RB',
            'team_abbr' => 'NYJ',
            'stat_type' => 'rushing_yards',
            'espn_stat' => 'rushingYards',
            'line'      => 66.5
        ],
        [
            'espn_id'   => 4426515,
            'name'      => 'Garrett Wilson',
            'position'  => 'WR',
            'team_abbr' => 'NYJ',
            'stat_type' => 'receiving_yards',
            'espn_stat' => 'receivingYards',
            'line'      => 68.5
        ],
        [
            'espn_id'   => 4430807,
            'name'      => 'Bijan Robinson',
            'position'  => 'RB',
            'team_abbr' => 'ATL',
            'stat_type' => 'rushing_yards',
            'espn_stat' => 'rushingYards',
            'line'      => 74.5
        ],
        [
            'espn_id'   => 4036378,
            'name'      => 'Jordan Love',
            'position'  => 'QB',
            'team_abbr' => 'GB',
            'stat_type' => 'passing_yards',
            'espn_stat' => 'passingYards',
            'line'      => 252.5
        ],
        [
            'espn_id'   => 4426348,
            'name'      => 'Jayden Daniels',
            'position'  => 'QB',
            'team_abbr' => 'WSH',
            'stat_type' => 'passing_yards',
            'espn_stat' => 'passingYards',
            'line'      => 230.5
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

        // Clean stale logs for this athlete to prevent duplicates with previous seeds/syncs
        $delStmt = $this->db->prepare("DELETE FROM player_game_logs WHERE player_id = :player_id");
        $delStmt->execute([':player_id' => $playerId]);

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
     * Synchronizes all 32 NFL franchises from ESPN API into MySQL `teams` table.
     * Ensures each team exists with sport_id = 1 and a valid defensive_rank (1-32).
     *
     * @return array Map of [abbreviation => team_id]
     */
    public function syncNflTeams(): array
    {
        $url = "{$this->siteBaseUrl}/football/nfl/teams?limit=35";
        $response = $this->executeCurl($url);

        if (!$response['success'] || !isset($response['data']['sports'][0]['leagues'][0]['teams'])) {
            return [];
        }

        $espnTeams = $response['data']['sports'][0]['leagues'][0]['teams'];
        $abbrMap = [];

        $stmt = $this->db->query("SELECT team_id, abbreviation FROM teams WHERE sport_id = 1");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $abbrMap[strtoupper($row['abbreviation'])] = (int) $row['team_id'];
        }

        $insertStmt = $this->db->prepare("INSERT INTO teams (sport_id, name, abbreviation, defensive_rank) VALUES (1, :name, :abbr, :rank)");

        foreach ($espnTeams as $idx => $t) {
            $tm = $t['team'];
            $abbr = strtoupper(trim($tm['abbreviation']));
            $name = trim($tm['displayName']);

            if (!isset($abbrMap[$abbr])) {
                $rank = $idx + 1;
                $insertStmt->execute([
                    ':name' => $name,
                    ':abbr' => $abbr,
                    ':rank' => $rank
                ]);
                $abbrMap[$abbr] = (int) $this->db->lastInsertId();
            }
        }

        return $abbrMap;
    }

    /**
     * Builds an in-memory dictionary of all 2,500+ active NFL players across 32 teams.
     *
     * @return array [normalized_name => ['name' => ..., 'position' => ..., 'team_abbr' => ..., 'espn_id' => ...]]
     */
    public function fetchRosterMap(): array
    {
        $url = "{$this->siteBaseUrl}/football/nfl/teams?limit=35";
        $response = $this->executeCurl($url);
        if (!$response['success'] || !isset($response['data']['sports'][0]['leagues'][0]['teams'])) {
            return [];
        }

        $espnTeams = $response['data']['sports'][0]['leagues'][0]['teams'];
        $dict = [];

        foreach ($espnTeams as $t) {
            $tm = $t['team'];
            $teamId = $tm['id'];
            $teamAbbr = strtoupper(trim($tm['abbreviation']));
            $teamName = trim($tm['displayName']);

            $rosterUrl = "{$this->siteBaseUrl}/football/nfl/teams/{$teamId}/roster";
            $rosterRes = $this->executeCurl($rosterUrl);
            if (!$rosterRes['success'] || !isset($rosterRes['data']['athletes'])) {
                continue;
            }

            foreach ($rosterRes['data']['athletes'] as $group) {
                foreach ($group['items'] ?? [] as $athlete) {
                    $fullName = trim($athlete['fullName'] ?? $athlete['displayName'] ?? '');
                    if ($fullName === '') continue;

                    $pos = strtoupper(trim($athlete['position']['abbreviation'] ?? 'ATH'));
                    $espnId = (int) ($athlete['id'] ?? 0);

                    $key = $this->normalizePlayerName($fullName);
                    $dict[$key] = [
                        'name'      => $fullName,
                        'position'  => $pos,
                        'team_abbr' => $teamAbbr,
                        'team_name' => $teamName,
                        'espn_id'   => $espnId
                    ];
                }
            }
        }

        return $dict;
    }

    /**
     * Searches ESPN search API for players not directly found in the 53-man active rosters
     * (e.g. players on injured reserve, practice squad, or spelling variations).
     */
    public function searchAthleteFallback(string $name): ?array
    {
        $searchUrl = "https://site.web.api.espn.com/apis/search/v2?query=" . urlencode($name) . "&limit=5";
        $response = $this->executeCurl($searchUrl);
        if (!$response['success'] || !isset($response['data']['results'])) {
            return null;
        }

        foreach ($response['data']['results'] as $res) {
            foreach ($res['contents'] ?? [] as $c) {
                if (($c['type'] ?? '') === 'player') {
                    $link = $c['link']['web'] ?? '';
                    if (preg_match('/\/id\/(\d+)/', $link, $matches)) {
                        $espnId = (int) $matches[1];
                        $athUrl = "{$this->commonBaseUrl}/football/nfl/athletes/{$espnId}";
                        $athRes = $this->executeCurl($athUrl);
                        $athlete = $athRes['data']['athlete'] ?? null;
                        if ($athlete) {
                            return [
                                'name'      => trim($athlete['displayName']),
                                'position'  => strtoupper(trim($athlete['position']['abbreviation'] ?? 'ATH')),
                                'team_abbr' => strtoupper(trim($athlete['team']['abbreviation'] ?? 'FA')),
                                'team_name' => trim($athlete['team']['displayName'] ?? 'Free Agent'),
                                'espn_id'   => $espnId
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Normalizes a player name by stripping accents, suffixes, punctuation, and extra whitespace.
     */
    public function normalizePlayerName(string $name): string
    {
        $name = str_replace(['é', 'è', 'ê', 'ë'], 'e', $name);
        $name = str_replace(['á', 'à', 'â', 'ä'], 'a', $name);
        $name = str_replace(['í', 'ì', 'î', 'ï'], 'i', $name);
        $name = str_replace(['ó', 'ò', 'ô', 'ö'], 'o', $name);
        $name = str_replace(['ú', 'ù', 'û', 'ü'], 'u', $name);
        $name = str_replace(['ñ'], 'n', $name);
        $name = preg_replace('/\b(Jr|Sr|III|II|IV)\.?\b/i', '', $name);
        $name = str_replace(['.', "'", '-', '"'], '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return strtolower(trim($name));
    }

    /**
     * Synchronizes an arbitrary list of player names by looking up their ESPN identity,
     * associating their real NFL team and position, and persisting them in MySQL `players`.
     *
     * @param array $names List of player names to resolve
     * @return array Detailed sync metrics
     */
    public function syncPlayerList(array $names): array
    {
        $abbrMap = $this->syncNflTeams();
        $rosterDict = $this->fetchRosterMap();

        $findPlayerStmt = $this->db->prepare("SELECT player_id, team_id, position, stat_average FROM players WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $insertPlayerStmt = $this->db->prepare("INSERT INTO players (team_id, name, position, stat_average) VALUES (:team_id, :name, :pos, :avg)");
        $updatePlayerStmt = $this->db->prepare("UPDATE players SET team_id = :team_id, position = :pos WHERE player_id = :id");

        $defaultAverages = [
            'QB'  => 248.50,
            'RB'  => 64.20,
            'WR'  => 58.80,
            'TE'  => 45.30,
            'FB'  => 12.00,
            'ATH' => 50.00
        ];

        $resolved = 0;
        $inserted = 0;
        $updated = 0;
        $fallbackUsed = 0;
        $unresolved = [];

        foreach ($names as $rawName) {
            $rawName = trim($rawName);
            if ($rawName === '') continue;

            $key = $this->normalizePlayerName($rawName);
            $athlete = $rosterDict[$key] ?? null;

            if (!$athlete) {
                $athlete = $this->searchAthleteFallback($rawName);
                if ($athlete) {
                    $fallbackUsed++;
                }
            }

            if (!$athlete) {
                $unresolved[] = $rawName;
                continue;
            }

            $resolved++;
            $officialName = $athlete['name'];
            $pos = $athlete['position'];
            $teamAbbr = $athlete['team_abbr'];
            $teamId = $abbrMap[$teamAbbr] ?? 1;
            $avg = $defaultAverages[$pos] ?? 50.00;

            $findPlayerStmt->execute([':name' => $officialName]);
            $existing = $findPlayerStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                $findPlayerStmt->execute([':name' => $rawName]);
                $existing = $findPlayerStmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($existing) {
                $updatePlayerStmt->execute([
                    ':team_id' => $teamId,
                    ':pos'     => $pos,
                    ':id'      => $existing['player_id']
                ]);
                $updated++;
            } else {
                $insertPlayerStmt->execute([
                    ':team_id' => $teamId,
                    ':name'    => $officialName,
                    ':pos'     => $pos,
                    ':avg'     => $avg
                ]);
                $inserted++;
            }
        }

        return [
            'success'        => true,
            'total_names'    => count($names),
            'resolved'       => $resolved,
            'fallback_used'  => $fallbackUsed,
            'inserted'       => $inserted,
            'updated'        => $updated,
            'unresolved'     => $unresolved
        ];
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

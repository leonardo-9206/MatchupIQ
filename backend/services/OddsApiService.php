<?php
/**
 * OddsApiService
 * Middleware service to consume The Odds API via cURL with local MySQL caching (15 min TTL)
 * and automatic synchronization with the relational `props` table.
 * Prevents redundant external calls, protects quota, and delivers sub-250ms responses.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/EdgeCalculatorService.php';

class OddsApiService
{
    private PDO $db;
    private string $apiKey;
    private string $baseUrl;
    private int $defaultTtl; // Cache validity duration in seconds (900 = 15 minutes)
    private bool $autoFetch; // Flag to prevent automatic external calls on cache expiration in dev

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
        $this->apiKey = getenv('ODDS_API_KEY') ?: '';
        $this->baseUrl = getenv('ODDS_API_BASE_URL') ?: 'https://api.the-odds-api.com/v4';
        $this->defaultTtl = 900; // 15 minutes as defined in HU-05

        // If ODDS_AUTO_FETCH is false, avoid triggering API calls on page reloads when cache expires
        $autoFetchEnv = getenv('ODDS_AUTO_FETCH');
        $this->autoFetch = $autoFetchEnv === false ? true : filter_var($autoFetchEnv, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Retrieve odds for a given sport and markets, checking local cache first.
     *
     * @param string $sportKey Sport identifier (e.g. 'americanfootball_nfl', 'soccer_mexico_ligamx')
     * @param array $markets Betting markets (e.g. ['h2h', 'spreads', 'totals'])
     * @param bool $forceRefresh Bypass cache and force a new external cURL call
     * @return array Standardized response containing data, source, and cache metadata
     */
    public function getOdds(string $sportKey = 'americanfootball_nfl', array $markets = ['h2h'], bool $forceRefresh = false): array
    {
        $marketsStr = implode(',', $markets);
        $cacheKey = "odds_{$sportKey}_{$marketsStr}";

        // 1. Check local cache if not forcing refresh
        if (!$forceRefresh) {
            $cached = $this->getFromCache($cacheKey);
            if ($cached !== null) {
                return [
                    'success'    => true,
                    'source'     => 'cache',
                    'cache_key'  => $cacheKey,
                    'fetched_at' => $cached['fetched_at'],
                    'expires_at' => $cached['expires_at'],
                    'ttl_left'   => max(0, strtotime($cached['expires_at']) - time()),
                    'count'      => count($cached['data']),
                    'data'       => $cached['data']
                ];
            }

            // If auto-fetch is disabled in development (ODDS_AUTO_FETCH=false),
            // reuse stale cache if available instead of consuming API quota on page reloads.
            if (!$this->autoFetch) {
                $stale = $this->getStaleCache($cacheKey);
                if ($stale !== null) {
                    return [
                        'success'    => true,
                        'source'     => 'cache_dev_lock',
                        'warning'    => 'ODDS_AUTO_FETCH is disabled. Returning existing MySQL cache without external request.',
                        'cache_key'  => $cacheKey,
                        'fetched_at' => $stale['fetched_at'],
                        'count'      => count($stale['data']),
                        'data'       => $stale['data']
                    ];
                }
            }
        }

        // 2. Fetch fresh data from The Odds API via cURL
        $endpoint = "/sports/{$sportKey}/odds/?apiKey={$this->apiKey}&regions=us&markets={$marketsStr}&oddsFormat=decimal";
        $url = $this->baseUrl . $endpoint;

        $apiResponse = $this->executeCurl($url);

        if (!$apiResponse['success']) {
            // Fallback: If external API call fails, check if stale cache exists to maintain uptime
            $stale = $this->getStaleCache($cacheKey);
            if ($stale !== null) {
                return [
                    'success'    => true,
                    'source'     => 'stale_cache_fallback',
                    'warning'    => 'External API failed, returning cached data: ' . $apiResponse['error'],
                    'cache_key'  => $cacheKey,
                    'fetched_at' => $stale['fetched_at'],
                    'data'       => $stale['data']
                ];
            }

            return [
                'success' => false,
                'source'  => 'api_error',
                'error'   => $apiResponse['error'],
                'data'    => []
            ];
        }

        $freshData = $apiResponse['data'];

        // 3. Persist fresh data in MySQL cache table
        $this->saveToCache($cacheKey, $sportKey, $freshData, $this->defaultTtl);

        // 4. Synchronize fresh odds into relational props table
        $syncStats = $this->syncOddsToProps($freshData);

        return [
            'success'            => true,
            'source'             => 'external_api',
            'cache_key'          => $cacheKey,
            'requests_remaining' => $apiResponse['requests_remaining'] ?? null,
            'count'              => count($freshData),
            'props_synced'       => $syncStats,
            'data'               => $freshData
        ];
    }

    /**
     * Retrieve active sports list from The Odds API with caching.
     */
    public function getSports(bool $forceRefresh = false): array
    {
        $cacheKey = 'active_sports_catalog';

        if (!$forceRefresh) {
            $cached = $this->getFromCache($cacheKey);
            if ($cached !== null) {
                return [
                    'success' => true,
                    'source'  => 'cache',
                    'data'    => $cached['data']
                ];
            }

            if (!$this->autoFetch) {
                $stale = $this->getStaleCache($cacheKey);
                if ($stale !== null) {
                    return [
                        'success' => true,
                        'source'  => 'cache_dev_lock',
                        'data'    => $stale['data']
                    ];
                }
            }
        }

        $url = "{$this->baseUrl}/sports/?apiKey={$this->apiKey}";
        $apiResponse = $this->executeCurl($url);

        if ($apiResponse['success']) {
            // Sports catalog cached for 24 hours (86400 seconds)
            $this->saveToCache($cacheKey, 'catalog', $apiResponse['data'], 86400);
            return [
                'success' => true,
                'source'  => 'external_api',
                'data'    => $apiResponse['data']
            ];
        }

        return $apiResponse;
    }

    /**
     * Synchronize odds data from The Odds API directly into the relational `props` table.
     * Maps bookmakers, games (by home/away teams), and player prop markets (or game totals).
     * Recomputes calculated_edge using EdgeCalculatorService with opposing defensive rankings.
     *
     * @param array $events Array of event/game odds structures from The Odds API
     * @return array Sync metrics including updated and inserted counts
     */
    public function syncOddsToProps(array $events): array
    {
        if (empty($events)) {
            return ['updated' => 0, 'inserted' => 0, 'events_processed' => 0];
        }

        $edgeCalculator = new EdgeCalculatorService();
        $updatedCount = 0;
        $insertedCount = 0;
        $eventsProcessed = 0;

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $homeTeam = trim($event['home_team'] ?? '');
            $awayTeam = trim($event['away_team'] ?? '');

            if ($homeTeam === '' || $awayTeam === '') {
                continue;
            }

            // 1. Resolve Game ID and Opposing Defensive Rankings from MySQL
            $gameSql = "SELECT g.game_id, g.home_team_id, g.away_team_id,
                               home_t.defensive_rank AS home_def_rank,
                               away_t.defensive_rank AS away_def_rank
                        FROM games g
                        INNER JOIN teams home_t ON g.home_team_id = home_t.team_id
                        INNER JOIN teams away_t ON g.away_team_id = away_t.team_id
                        WHERE (LOWER(home_t.name) = LOWER(:home_name) OR LOWER(home_t.abbreviation) = LOWER(:home_abbr))
                          AND (LOWER(away_t.name) = LOWER(:away_name) OR LOWER(away_t.abbreviation) = LOWER(:away_abbr))
                        LIMIT 1";
            $gameStmt = $this->db->prepare($gameSql);
            $gameStmt->execute([
                ':home_name' => $homeTeam,
                ':home_abbr' => $homeTeam,
                ':away_name' => $awayTeam,
                ':away_abbr' => $awayTeam
            ]);
            $gameRow = $gameStmt->fetch();

            if (!$gameRow) {
                // Fuzzy fallback: match either team
                $fuzzyGameSql = "SELECT g.game_id, g.home_team_id, g.away_team_id,
                                        home_t.defensive_rank AS home_def_rank,
                                        away_t.defensive_rank AS away_def_rank
                                 FROM games g
                                 INNER JOIN teams home_t ON g.home_team_id = home_t.team_id
                                 INNER JOIN teams away_t ON g.away_team_id = away_t.team_id
                                 WHERE LOWER(home_t.name) = LOWER(:fuzzy_home) OR LOWER(away_t.name) = LOWER(:fuzzy_away)
                                 LIMIT 1";
                $fuzzyStmt = $this->db->prepare($fuzzyGameSql);
                $fuzzyStmt->execute([':fuzzy_home' => $homeTeam, ':fuzzy_away' => $awayTeam]);
                $gameRow = $fuzzyStmt->fetch();
            }

            // Dynamic schedule sync: if game not found in calendar, insert it from real event payload
            if (!$gameRow) {
                $teamLookupSql = "SELECT team_id, defensive_rank FROM teams 
                                  WHERE LOWER(name) = LOWER(:tname) OR LOWER(abbreviation) = LOWER(:tabbr) 
                                  LIMIT 1";
                $tLookupStmt = $this->db->prepare($teamLookupSql);

                $tLookupStmt->execute([':tname' => $homeTeam, ':tabbr' => $homeTeam]);
                $homeTeamRow = $tLookupStmt->fetch();

                $tLookupStmt->execute([':tname' => $awayTeam, ':tabbr' => $awayTeam]);
                $awayTeamRow = $tLookupStmt->fetch();

                if ($homeTeamRow && $awayTeamRow) {
                    $homeTeamId = (int) $homeTeamRow['team_id'];
                    $awayTeamId = (int) $awayTeamRow['team_id'];
                    $homeDefRank = (int) $homeTeamRow['defensive_rank'];
                    $awayDefRank = (int) $awayTeamRow['defensive_rank'];

                    $gameDateRaw = $event['commence_time'] ?? '';
                    $gameDate = strlen($gameDateRaw) >= 19 
                        ? str_replace(['T', 'Z'], [' ', ''], substr($gameDateRaw, 0, 19)) 
                        : date('Y-m-d H:i:s', time() + 86400);

                    $insertGameSql = "INSERT INTO games (sport_id, home_team_id, away_team_id, game_date) 
                                      VALUES (1, :home_id, :away_id, :gdate)";
                    $insertGameStmt = $this->db->prepare($insertGameSql);
                    $insertGameStmt->execute([
                        ':home_id' => $homeTeamId,
                        ':away_id' => $awayTeamId,
                        ':gdate'   => $gameDate
                    ]);
                    $gameId = (int) $this->db->lastInsertId();

                    $gameRow = [
                        'game_id'       => $gameId,
                        'home_team_id'  => $homeTeamId,
                        'away_team_id'  => $awayTeamId,
                        'home_def_rank' => $homeDefRank,
                        'away_def_rank' => $awayDefRank
                    ];
                }
            }

            if (!$gameRow) {
                continue;
            }

            $gameId = (int) $gameRow['game_id'];
            $homeTeamId = (int) $gameRow['home_team_id'];
            $homeDefRank = (int) $gameRow['home_def_rank'];
            $awayDefRank = (int) $gameRow['away_def_rank'];
            $eventsProcessed++;

            $bookmakers = $event['bookmakers'] ?? [];
            if (!is_array($bookmakers)) {
                continue;
            }

            foreach ($bookmakers as $bm) {
                $bmTitle = trim($bm['title'] ?? $bm['key'] ?? '');
                if ($bmTitle === '') {
                    continue;
                }

                // 2. Resolve Bookmaker ID
                $bmId = $this->resolveBookmakerId($bmTitle);

                $markets = $bm['markets'] ?? [];
                if (!is_array($markets)) {
                    continue;
                }

                foreach ($markets as $mkt) {
                    $mktKey = $mkt['key'] ?? '';
                    $outcomes = $mkt['outcomes'] ?? [];
                    if (!is_array($outcomes)) {
                        continue;
                    }

                    $betType = $this->normalizeBetType($mktKey);

                    // Group player prop outcomes if 'description' contains player name
                    $playerOutcomes = [];
                    $isPlayerProp = false;

                    foreach ($outcomes as $outcome) {
                        if (isset($outcome['description']) && trim($outcome['description']) !== '') {
                            $isPlayerProp = true;
                            $pName = trim($outcome['description']);
                            $side = strtolower(trim($outcome['name'] ?? ''));
                            $playerOutcomes[$pName][$side] = [
                                'price' => (float) ($outcome['price'] ?? 1.90),
                                'point' => isset($outcome['point']) ? (float) $outcome['point'] : null
                            ];
                        }
                    }

                    if ($isPlayerProp) {
                        foreach ($playerOutcomes as $playerName => $sides) {
                            $playerRow = $this->resolvePlayer($playerName);
                            if (!$playerRow) {
                                continue;
                            }

                            $playerId = (int) $playerRow['player_id'];
                            $statAvg = (float) $playerRow['stat_average'];
                            $playerTeamId = (int) $playerRow['team_id'];

                            // Opposing defense ranking for Edge calculation
                            $opposingRank = ($playerTeamId === $homeTeamId) ? $awayDefRank : $homeDefRank;

                            $oddsOver = (float) ($sides['over']['price'] ?? $sides['under']['price'] ?? 1.90);
                            $oddsUnder = (float) ($sides['under']['price'] ?? $sides['over']['price'] ?? 1.90);
                            $lineOver = (float) ($sides['over']['point'] ?? $sides['under']['point'] ?? $statAvg);

                            // Recalculate Value Edge with real parameters
                            $edgeAnalysis = $edgeCalculator->calculateEdge($statAvg, $lineOver, $oddsOver, $opposingRank);
                            $calculatedEdge = (float) ($edgeAnalysis['calculated_edge'] ?? 0.0);

                            $status = $this->upsertProp(
                                $playerId,
                                $bmId,
                                $gameId,
                                $betType,
                                $lineOver,
                                $oddsOver,
                                $oddsUnder,
                                $calculatedEdge
                            );

                            if ($status === 'inserted') {
                                $insertedCount++;
                            } elseif ($status === 'updated') {
                                $updatedCount++;
                            }
                        }
                    } else {
                        // Game-level totals/h2h: sync against existing props for this game & bookmaker
                        $overPrice = null;
                        $underPrice = null;
                        $point = null;

                        foreach ($outcomes as $outcome) {
                            $name = strtolower(trim($outcome['name'] ?? ''));
                            if ($name === 'over') {
                                $overPrice = (float) ($outcome['price'] ?? 1.90);
                                if (isset($outcome['point'])) {
                                    $point = (float) $outcome['point'];
                                }
                            } elseif ($name === 'under') {
                                $underPrice = (float) ($outcome['price'] ?? 1.90);
                                if (isset($outcome['point'])) {
                                    $point = (float) $outcome['point'];
                                }
                            }
                        }

                        if ($overPrice !== null) {
                            $underPrice = $underPrice ?? $overPrice;
                            $count = $this->updateExistingGameProps(
                                $gameId,
                                $bmId,
                                $overPrice,
                                $underPrice,
                                $point,
                                $edgeCalculator
                            );
                            $updatedCount += $count;
                        }
                    }
                }
            }
        }

        return [
            'updated'          => $updatedCount,
            'inserted'         => $insertedCount,
            'events_processed' => $eventsProcessed
        ];
    }

    /**
     * Resolves bookmaker ID by matching existing names or inserting a new bookmaker.
     */
    private function resolveBookmakerId(string $name): int
    {
        $stmt = $this->db->prepare("SELECT bookmaker_id FROM bookmakers WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch();

        if ($row) {
            return (int) $row['bookmaker_id'];
        }

        // Insert newly discovered bookmaker
        $insert = $this->db->prepare("INSERT INTO bookmakers (name) VALUES (:name)");
        $insert->execute([':name' => $name]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Resolves player record from MySQL by name with fuzzy fallback.
     */
    private function resolvePlayer(string $name): ?array
    {
        $stmt = $this->db->prepare("SELECT player_id, team_id, stat_average FROM players WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        // Fuzzy match: last name lookup
        $parts = explode(' ', trim($name));
        $lastName = end($parts);
        if (strlen($lastName) >= 3) {
            $fuzzy = $this->db->prepare("SELECT player_id, team_id, stat_average FROM players WHERE LOWER(name) LIKE :like LIMIT 1");
            $fuzzy->execute([':like' => '%' . strtolower($lastName) . '%']);
            $fuzzyRow = $fuzzy->fetch();
            if ($fuzzyRow) {
                return $fuzzyRow;
            }
        }

        return null;
    }

    /**
     * Normalizes The Odds API market keys to standard database bet_type descriptors.
     */
    private function normalizeBetType(string $key): string
    {
        return match ($key) {
            'player_pass_yds'      => 'passing_yards',
            'player_rush_yds'      => 'rushing_yards',
            'player_reception_yds' => 'receiving_yards',
            'player_pass_tds'      => 'passing_touchdowns',
            'totals'               => 'game_total',
            'h2h'                  => 'moneyline',
            'spreads'              => 'spread',
            default                => str_replace(['player_', '_yds'], ['', '_yards'], $key)
        };
    }

    /**
     * Updates an existing prop or inserts a new record with calculated edge.
     */
    private function upsertProp(
        int $playerId,
        int $bookmakerId,
        int $gameId,
        string $betType,
        float $lineOver,
        float $oddsOver,
        float $oddsUnder,
        float $calculatedEdge
    ): string {
        $checkStmt = $this->db->prepare("SELECT prop_id FROM props 
            WHERE player_id = :player_id 
              AND bookmaker_id = :bookmaker_id 
              AND game_id = :game_id 
              AND bet_type = :bet_type 
            LIMIT 1");
        $checkStmt->execute([
            ':player_id'    => $playerId,
            ':bookmaker_id' => $bookmakerId,
            ':game_id'      => $gameId,
            ':bet_type'     => $betType
        ]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $update = $this->db->prepare("UPDATE props SET 
                line_over = :line_over,
                odds_over = :odds_over,
                odds_under = :odds_under,
                calculated_edge = :calculated_edge,
                updated_at = NOW()
                WHERE prop_id = :prop_id");
            $update->execute([
                ':line_over'       => $lineOver,
                ':odds_over'       => $oddsOver,
                ':odds_under'      => $oddsUnder,
                ':calculated_edge' => $calculatedEdge,
                ':prop_id'         => (int) $existing['prop_id']
            ]);
            return 'updated';
        }

        $insert = $this->db->prepare("INSERT INTO props 
            (player_id, bookmaker_id, game_id, bet_type, line_over, odds_over, odds_under, calculated_edge, updated_at)
            VALUES 
            (:player_id, :bookmaker_id, :game_id, :bet_type, :line_over, :odds_over, :odds_under, :calculated_edge, NOW())");
        $insert->execute([
            ':player_id'       => $playerId,
            ':bookmaker_id'    => $bookmakerId,
            ':game_id'         => $gameId,
            ':bet_type'        => $betType,
            ':line_over'       => $lineOver,
            ':odds_over'       => $oddsOver,
            ':odds_under'      => $oddsUnder,
            ':calculated_edge' => $calculatedEdge
        ]);
        return 'inserted';
    }

    /**
     * Updates existing props for a game and bookmaker when totals or general game lines refresh.
     */
    private function updateExistingGameProps(
        int $gameId,
        int $bookmakerId,
        float $oddsOver,
        float $oddsUnder,
        ?float $line,
        EdgeCalculatorService $edgeCalculator
    ): int {
        $sql = "SELECT p.prop_id, p.line_over, pl.stat_average,
                       CASE WHEN g.home_team_id = pl.team_id THEN away_t.defensive_rank ELSE home_t.defensive_rank END AS opposing_rank
                FROM props p
                INNER JOIN players pl ON p.player_id = pl.player_id
                INNER JOIN games g ON p.game_id = g.game_id
                INNER JOIN teams home_t ON g.home_team_id = home_t.team_id
                INNER JOIN teams away_t ON g.away_team_id = away_t.team_id
                WHERE p.game_id = :game_id AND p.bookmaker_id = :bookmaker_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':game_id' => $gameId, ':bookmaker_id' => $bookmakerId]);
        $rows = $stmt->fetchAll();

        $updated = 0;
        foreach ($rows as $row) {
            $effectiveLine = $line ?? (float) $row['line_over'];
            $edgeAnalysis = $edgeCalculator->calculateEdge(
                (float) $row['stat_average'],
                $effectiveLine,
                $oddsOver,
                (int) $row['opposing_rank']
            );
            $calculatedEdge = (float) ($edgeAnalysis['calculated_edge'] ?? 0.0);

            $update = $this->db->prepare("UPDATE props SET
                odds_over = :odds_over,
                odds_under = :odds_under,
                line_over = :line_over,
                calculated_edge = :calculated_edge,
                updated_at = NOW()
                WHERE prop_id = :prop_id");
            $update->execute([
                ':odds_over'       => $oddsOver,
                ':odds_under'      => $oddsUnder,
                ':line_over'       => $effectiveLine,
                ':calculated_edge' => $calculatedEdge,
                ':prop_id'         => (int) $row['prop_id']
            ]);
            $updated++;
        }

        return $updated;
    }

    /**
     * Check if a valid, unexpired cache record exists in MySQL.
     */
    private function getFromCache(string $cacheKey): ?array
    {
        $sql = "SELECT `response_data`, `fetched_at`, `expires_at` 
                FROM `odds_cache` 
                WHERE `cache_key` = :cache_key 
                  AND `expires_at` > NOW() 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cache_key' => $cacheKey]);
        $row = $stmt->fetch();

        if ($row) {
            $decoded = json_decode($row['response_data'], true);
            if (is_array($decoded)) {
                return [
                    'data'       => $decoded,
                    'fetched_at' => $row['fetched_at'],
                    'expires_at' => $row['expires_at']
                ];
            }
        }

        return null;
    }

    /**
     * Fetch stale cache if available (for error resilience / network failure).
     */
    private function getStaleCache(string $cacheKey): ?array
    {
        $sql = "SELECT `response_data`, `fetched_at` 
                FROM `odds_cache` 
                WHERE `cache_key` = :cache_key 
                ORDER BY `fetched_at` DESC 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cache_key' => $cacheKey]);
        $row = $stmt->fetch();

        if ($row) {
            $decoded = json_decode($row['response_data'], true);
            if (is_array($decoded)) {
                return [
                    'data'       => $decoded,
                    'fetched_at' => $row['fetched_at']
                ];
            }
        }

        return null;
    }

    /**
     * Store or update API response in MySQL cache with calculated expiration timestamp.
     */
    private function saveToCache(string $cacheKey, string $sportKey, array $data, int $ttlSeconds): void
    {
        $encoded = json_encode($data);

        $sql = "INSERT INTO `odds_cache` (`cache_key`, `sport_key`, `response_data`, `fetched_at`, `expires_at`)
                VALUES (:cache_key, :sport_key, :response_data, NOW(), DATE_ADD(NOW(), INTERVAL :ttl SECOND))
                ON DUPLICATE KEY UPDATE 
                    `response_data` = VALUES(`response_data`),
                    `fetched_at`    = VALUES(`fetched_at`),
                    `expires_at`    = VALUES(`expires_at`)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cache_key', $cacheKey, PDO::PARAM_STR);
        $stmt->bindValue(':sport_key', $sportKey, PDO::PARAM_STR);
        $stmt->bindValue(':response_data', $encoded, PDO::PARAM_STR);
        $stmt->bindValue(':ttl', $ttlSeconds, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Perform an HTTP GET request using cURL with error handling and quota tracking.
     */
    private function executeCurl(string $url): array
    {
        $ch = curl_init();

        $responseHeaders = [];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: MatchupIQ-Proxy/1.0'
            ],
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$responseHeaders) {
                $len = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $len;
            }
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
            $msg = "External API responded with HTTP {$httpCode}";
            if ($httpCode === 401) {
                $msg = "Invalid or missing The Odds API key (HTTP 401).";
            } elseif ($httpCode === 429) {
                $msg = "The Odds API monthly quota exceeded (HTTP 429).";
            }
            return [
                'success'   => false,
                'http_code' => $httpCode,
                'error'     => $msg
            ];
        }

        $decoded = json_decode($body, true);
        if ($decoded === null) {
            return [
                'success' => false,
                'error'   => 'Failed to parse JSON response from external API.'
            ];
        }

        return [
            'success'            => true,
            'data'               => $decoded,
            'requests_remaining' => $responseHeaders['x-requests-remaining'] ?? null
        ];
    }

    /**
     * Housekeeping: Purge expired cache rows older than specified minutes.
     */
    public function purgeExpiredCache(int $olderThanMinutes = 60): int
    {
        $sql = "DELETE FROM `odds_cache` WHERE `expires_at` < DATE_SUB(NOW(), INTERVAL :mins MINUTE)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':mins', $olderThanMinutes, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}

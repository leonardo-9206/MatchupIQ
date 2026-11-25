<?php
/**
 * AdminController
 * Handles administrative system operations, catalog maintenance, and quota-sensitive sync tasks.
 * All actions are strictly guarded by AuthMiddleware::requireRole('admin').
 */

require_once __DIR__ . '/../services/OddsApiService.php';
require_once __DIR__ . '/../services/EspnApiService.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Response.php';

class AdminController
{
    private OddsApiService $oddsService;
    private EspnApiService $espnService;

    public function __construct()
    {
        $this->oddsService = new OddsApiService();
        $this->espnService = new EspnApiService();
    }

    /**
     * Forces an on-demand synchronization with The Odds API and updates local MySQL cache
     * as well as the relational `props` table with recalculated Value Edge metrics.
     * Restricted strictly to administrators to prevent depletion of monthly API quotas.
     * Route: POST /api/admin/odds/refresh.php
     */
    public function refreshOdds(): void
    {
        // 1. RBAC Guard: Verify session and require 'admin' role
        $adminUser = AuthMiddleware::requireRole('admin');

        // 2. Parse payload (JSON body or form data)
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $sport = isset($data['sport']) && is_string($data['sport']) && trim($data['sport']) !== ''
            ? trim($data['sport'])
            : 'americanfootball_nfl';

        $markets = isset($data['markets']) && is_array($data['markets']) && !empty($data['markets'])
            ? $data['markets']
            : ['h2h'];

        $startTime = microtime(true);

        try {
            // Force refresh bypasses local cache, hits external API via cURL, and syncs relational props
            $result = $this->oddsService->getOdds($sport, $markets, true);
            $playerPropsSync = $this->oddsService->syncAllLivePlayerProps($sport);
            $this->espnService->syncDefensiveRankings();

            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            if (!$result['success']) {
                $statusCode = isset($result['http_code']) && is_int($result['http_code']) ? $result['http_code'] : 502;
                Response::error(
                    $result['error'] ?? 'External odds synchronization failed.',
                    $statusCode,
                    ['source' => $result['source'] ?? 'api_error']
                );
            }

            $propsSynced = [
                'updated'          => ($result['props_synced']['updated'] ?? 0) + $playerPropsSync['updated'],
                'inserted'         => ($result['props_synced']['inserted'] ?? 0) + $playerPropsSync['inserted'],
                'events_processed' => max($result['props_synced']['events_processed'] ?? 0, $playerPropsSync['events_processed'])
            ];

            Response::success('The Odds API cache and relational props synchronized successfully.', [
                'triggered_by'       => $adminUser['email'],
                'benchmark_ms'       => $executionTimeMs,
                'sport_key'          => $sport,
                'markets'            => $markets,
                'requests_remaining' => $result['requests_remaining'] ?? null,
                'cached_records'     => $result['count'] ?? 0,
                'props_updated'      => $propsSynced['updated'] ?? 0,
                'props_inserted'     => $propsSynced['inserted'] ?? 0,
                'events_processed'   => $propsSynced['events_processed'] ?? 0,
                'expires_at'         => date('Y-m-d H:i:s', time() + 900)
            ], 200);

        } catch (Throwable $e) {
            error_log('AdminController Refresh Odds Error: ' . $e->getMessage());
            Response::error('Internal server error during odds refresh: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Triggers live ingestion of recent game logs and athlete catalog expansion from ESPN API.
     * Route: POST /api/admin/espn/sync.php
     * Access: Administrator role only
     */
    public function syncEspnData(): void
    {
        // 1. RBAC Guard: Verify session and require 'admin' role
        $adminUser = AuthMiddleware::requireRole('admin');

        $startTime = microtime(true);

        try {
            $result = $this->espnService->syncAllPresetAthletes(5);
            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            Response::success('ESPN athlete game logs synchronized successfully.', array_merge($result, [
                'triggered_by' => $adminUser['email'],
                'benchmark_ms' => $executionTimeMs
            ]), 200);

        } catch (Throwable $e) {
            error_log('AdminController ESPN Sync Error: ' . $e->getMessage());
            Response::error('Internal server error during ESPN synchronization: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Retrieves database health metrics, table row counts, and sync status for the admin panel.
     * Route: GET /api/admin/stats.php
     * Access: Administrator role only
     */
    public function getDatabaseStats(): void
    {
        // 1. RBAC Guard: Verify session and require 'admin' role
        $adminUser = AuthMiddleware::requireRole('admin');

        $startTime = microtime(true);

        try {
            $db = Database::getInstance()->getConnection();

            // Measure database ping latency
            $pingStart = microtime(true);
            $db->query("SELECT 1")->fetchColumn();
            $pingMs = round((microtime(true) - $pingStart) * 1000, 2);

            // Fetch table counts
            $counts = [
                'users'            => (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'players'          => (int) $db->query("SELECT COUNT(*) FROM players")->fetchColumn(),
                'teams'            => (int) $db->query("SELECT COUNT(*) FROM teams")->fetchColumn(),
                'games'            => (int) $db->query("SELECT COUNT(*) FROM games")->fetchColumn(),
                'props'            => (int) $db->query("SELECT COUNT(*) FROM props")->fetchColumn(),
                'bet_tracker'      => (int) $db->query("SELECT COUNT(*) FROM bet_tracker")->fetchColumn(),
                'player_game_logs' => (int) $db->query("SELECT COUNT(*) FROM player_game_logs")->fetchColumn(),
                'player_heatmaps'  => (int) $db->query("SELECT COUNT(*) FROM player_heatmaps")->fetchColumn(),
                'coverage_splits'  => (int) $db->query("SELECT COUNT(*) FROM coverage_splits")->fetchColumn(),
            ];

            // Cache & Sync indicators
            $latestCacheStmt = $db->query("SELECT fetched_at, expires_at FROM odds_cache ORDER BY fetched_at DESC LIMIT 1");
            $latestCache = $latestCacheStmt->fetch(PDO::FETCH_ASSOC);

            $latestLogStmt = $db->query("SELECT MAX(game_date) AS latest_game FROM player_game_logs");
            $latestLog = $latestLogStmt->fetch(PDO::FETCH_ASSOC);

            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            Response::success('Database statistics and infrastructure metrics retrieved successfully.', [
                'requested_by' => $adminUser['email'],
                'ping_ms'      => $pingMs,
                'benchmark_ms' => $executionTimeMs,
                'counts'       => $counts,
                'odds_cache'   => [
                    'active'     => !empty($latestCache),
                    'fetched_at' => $latestCache['fetched_at'] ?? null,
                    'expires_at' => $latestCache['expires_at'] ?? null,
                ],
                'espn_sync'    => [
                    'latest_game_date' => $latestLog['latest_game'] ?? null,
                ],
                'database'     => [
                    'engine' => 'MySQL / TiDB Cloud',
                    'status' => 'connected',
                ]
            ], 200);

        } catch (Throwable $e) {
            error_log('AdminController Database Stats Error: ' . $e->getMessage());
            Response::error('Failed to retrieve database metrics: ' . $e->getMessage(), 500);
        }
    }
}

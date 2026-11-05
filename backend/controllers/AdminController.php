<?php
/**
 * AdminController
 * Handles administrative system operations, catalog maintenance, and quota-sensitive sync tasks.
 * All actions are strictly guarded by AuthMiddleware::requireRole('admin').
 */

require_once __DIR__ . '/../services/OddsApiService.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../config/Response.php';

class AdminController
{
    private OddsApiService $oddsService;

    public function __construct()
    {
        $this->oddsService = new OddsApiService();
    }

    /**
     * Forces an on-demand synchronization with The Odds API and updates local MySQL cache.
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
            // Force refresh bypasses local cache and hits external API via cURL
            $result = $this->oddsService->getOdds($sport, $markets, true);

            $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            if (!$result['success']) {
                $statusCode = isset($result['http_code']) && is_int($result['http_code']) ? $result['http_code'] : 502;
                Response::error(
                    $result['error'] ?? 'External odds synchronization failed.',
                    $statusCode,
                    ['source' => $result['source'] ?? 'api_error']
                );
            }

            Response::success('The Odds API cache synchronized successfully.', [
                'triggered_by'       => $adminUser['email'],
                'benchmark_ms'       => $executionTimeMs,
                'sport_key'          => $sport,
                'markets'            => $markets,
                'requests_remaining' => $result['requests_remaining'] ?? null,
                'cached_records'     => $result['count'] ?? 0,
                'expires_at'         => date('Y-m-d H:i:s', time() + 900)
            ], 200);

        } catch (Throwable $e) {
            error_log('AdminController Refresh Odds Error: ' . $e->getMessage());
            Response::error('Internal server error during odds refresh: ' . $e->getMessage(), 500);
        }
    }
}

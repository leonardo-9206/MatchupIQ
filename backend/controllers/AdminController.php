<?php
/**
 * AdminController
 * Handles administrative system operations, catalog maintenance, and quota-sensitive sync tasks.
 * All actions are strictly guarded by AuthMiddleware::requireRole('admin').
 */

require_once __DIR__ . '/../services/OddsApiService.php';
require_once __DIR__ . '/../services/EspnApiService.php';
require_once __DIR__ . '/../models/Team.php';
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/Validator.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Response.php';

class AdminController
{
    private OddsApiService $oddsService;
    private EspnApiService $espnService;
    private Team $teamModel;
    private Player $playerModel;

    public function __construct()
    {
        $this->oddsService = new OddsApiService();
        $this->espnService = new EspnApiService();
        $this->teamModel = new Team();
        $this->playerModel = new Player();
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

    /**
     * Lists all teams in the catalog.
     * Route: GET /api/admin/teams/index.php
     */
    public function listTeams(): void
    {
        AuthMiddleware::requireRole('admin');

        try {
            $teams = $this->teamModel->getAll();
            Response::success('Teams retrieved successfully.', [
                'total' => count($teams),
                'teams' => $teams
            ], 200);
        } catch (Throwable $e) {
            error_log('AdminController List Teams Error: ' . $e->getMessage());
            Response::error('Failed to retrieve teams catalog.', 500);
        }
    }

    /**
     * Creates a new team in the catalog.
     * Route: POST /api/admin/teams/create.php
     */
    public function createTeam(): void
    {
        AuthMiddleware::requireRole('admin');
        $input = $this->getJsonBody();

        $validator = Validator::make($input, [
            'sport_id'       => 'required|integer|min:1',
            'name'           => 'required|min:2|max:100',
            'abbreviation'   => 'required|min:2|max:10',
            'defensive_rank' => 'required|integer|min:1|max:32',
        ]);

        if ($validator->fails()) {
            Response::error('Validation failed for team creation.', 422, $validator->errors());
        }

        $data = $validator->validated();
        $sportId = (int) $data['sport_id'];

        try {
            if (!$this->teamModel->sportExists($sportId)) {
                Response::error('The specified sport_id does not exist.', 422, ['sport_id' => ['Invalid sport_id.']]);
            }

            $team = $this->teamModel->create(
                $sportId,
                (string) $data['name'],
                (string) $data['abbreviation'],
                (int) $data['defensive_rank']
            );

            Response::success('Team created successfully.', ['team' => $team], 201);
        } catch (Throwable $e) {
            error_log('AdminController Create Team Error: ' . $e->getMessage());
            Response::error('Failed to create team.', 500);
        }
    }

    /**
     * Updates an existing team in the catalog.
     * Route: PUT /api/admin/teams/update.php
     */
    public function updateTeam(): void
    {
        AuthMiddleware::requireRole('admin');
        $input = $this->getJsonBody();

        $validator = Validator::make($input, [
            'team_id'        => 'required|integer|min:1',
            'sport_id'       => 'integer|min:1',
            'name'           => 'min:2|max:100',
            'abbreviation'   => 'min:2|max:10',
            'defensive_rank' => 'integer|min:1|max:32',
        ]);

        if ($validator->fails()) {
            Response::error('Validation failed for team update.', 422, $validator->errors());
        }

        $data = $validator->validated();
        $teamId = (int) $data['team_id'];

        try {
            if (isset($data['sport_id']) && !$this->teamModel->sportExists((int) $data['sport_id'])) {
                Response::error('The specified sport_id does not exist.', 422, ['sport_id' => ['Invalid sport_id.']]);
            }

            $updated = $this->teamModel->update($teamId, $data);
            if (!$updated) {
                Response::error('Team not found.', 404);
            }

            Response::success('Team updated successfully.', ['team' => $updated], 200);
        } catch (Throwable $e) {
            error_log('AdminController Update Team Error: ' . $e->getMessage());
            Response::error('Failed to update team.', 500);
        }
    }

    /**
     * Deletes a team from the catalog.
     * Route: DELETE /api/admin/teams/delete.php?id=1
     */
    public function deleteTeam(): void
    {
        AuthMiddleware::requireRole('admin');
        $teamId = $this->resolveIdParam('team_id');

        if ($teamId === null) {
            Response::error('A valid integer "id" parameter is required.', 400);
        }

        try {
            $deleted = $this->teamModel->delete($teamId);
            if (!$deleted) {
                Response::error('Team not found.', 404);
            }

            Response::success('Team deleted successfully.', ['team_id' => $teamId], 200);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Response::error('Cannot delete team because it has associated players or games.', 409);
            }
            error_log('AdminController Delete Team PDO Error: ' . $e->getMessage());
            Response::error('Failed to delete team.', 500);
        } catch (Throwable $e) {
            error_log('AdminController Delete Team Error: ' . $e->getMessage());
            Response::error('Failed to delete team.', 500);
        }
    }

    /**
     * Lists all players in the catalog.
     * Route: GET /api/admin/players/index.php
     */
    public function listPlayers(): void
    {
        AuthMiddleware::requireRole('admin');

        try {
            $players = $this->playerModel->getAll();
            Response::success('Players retrieved successfully.', [
                'total'   => count($players),
                'players' => $players
            ], 200);
        } catch (Throwable $e) {
            error_log('AdminController List Players Error: ' . $e->getMessage());
            Response::error('Failed to retrieve players catalog.', 500);
        }
    }

    /**
     * Creates a new player in the catalog.
     * Route: POST /api/admin/players/create.php
     */
    public function createPlayer(): void
    {
        AuthMiddleware::requireRole('admin');
        $input = $this->getJsonBody();

        $validator = Validator::make($input, [
            'team_id'      => 'required|integer|min:1',
            'name'         => 'required|min:2|max:100',
            'position'     => 'required|min:1|max:20',
            'stat_average' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Response::error('Validation failed for player creation.', 422, $validator->errors());
        }

        $data = $validator->validated();
        $teamId = (int) $data['team_id'];

        try {
            if (!$this->playerModel->teamExists($teamId)) {
                Response::error('The specified team_id does not exist.', 422, ['team_id' => ['Invalid team_id.']]);
            }

            $player = $this->playerModel->create(
                $teamId,
                (string) $data['name'],
                (string) $data['position'],
                (float) $data['stat_average']
            );

            Response::success('Player created successfully.', ['player' => $player], 201);
        } catch (Throwable $e) {
            error_log('AdminController Create Player Error: ' . $e->getMessage());
            Response::error('Failed to create player.', 500);
        }
    }

    /**
     * Updates an existing player in the catalog.
     * Route: PUT /api/admin/players/update.php
     */
    public function updatePlayer(): void
    {
        AuthMiddleware::requireRole('admin');
        $input = $this->getJsonBody();

        $validator = Validator::make($input, [
            'player_id'    => 'required|integer|min:1',
            'team_id'      => 'integer|min:1',
            'name'         => 'min:2|max:100',
            'position'     => 'min:1|max:20',
            'stat_average' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            Response::error('Validation failed for player update.', 422, $validator->errors());
        }

        $data = $validator->validated();
        $playerId = (int) $data['player_id'];

        try {
            if (isset($data['team_id']) && !$this->playerModel->teamExists((int) $data['team_id'])) {
                Response::error('The specified team_id does not exist.', 422, ['team_id' => ['Invalid team_id.']]);
            }

            $updated = $this->playerModel->update($playerId, $data);
            if (!$updated) {
                Response::error('Player not found.', 404);
            }

            Response::success('Player updated successfully.', ['player' => $updated], 200);
        } catch (Throwable $e) {
            error_log('AdminController Update Player Error: ' . $e->getMessage());
            Response::error('Failed to update player.', 500);
        }
    }

    /**
     * Deletes a player from the catalog.
     * Route: DELETE /api/admin/players/delete.php?id=1
     */
    public function deletePlayer(): void
    {
        AuthMiddleware::requireRole('admin');
        $playerId = $this->resolveIdParam('player_id');

        if ($playerId === null) {
            Response::error('A valid integer "id" parameter is required.', 400);
        }

        try {
            $deleted = $this->playerModel->delete($playerId);
            if (!$deleted) {
                Response::error('Player not found.', 404);
            }

            Response::success('Player deleted successfully.', ['player_id' => $playerId], 200);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Response::error('Cannot delete player because they have associated props or records.', 409);
            }
            error_log('AdminController Delete Player PDO Error: ' . $e->getMessage());
            Response::error('Failed to delete player.', 500);
        } catch (Throwable $e) {
            error_log('AdminController Delete Player Error: ' . $e->getMessage());
            Response::error('Failed to delete player.', 500);
        }
    }

    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return is_array($_POST) ? $_POST : [];
    }

    private function resolveIdParam(string $bodyKey): ?int
    {
        $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;
        if (!$id) {
            $input = $this->getJsonBody();
            $id = isset($input[$bodyKey]) ? filter_var($input[$bodyKey], FILTER_VALIDATE_INT) : false;
        }
        return ($id && $id > 0) ? (int) $id : null;
    }
}

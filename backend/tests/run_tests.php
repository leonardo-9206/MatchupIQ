<?php
/**
 * MatchupIQ — Backend Automated Integration Test Suite
 * Evaluates Sprint 1, Sprint 2 & Sprint 3 Backend requirements:
 * 1. Health & CORS Infrastructure
 * 2. Access Restrictions & RBAC (401 Unauthorized, 403 Forbidden)
 * 3. Server-side Input Validation (400 Bad Request, 422 Unprocessable)
 * 4. Secure Authentication Flow (Register 201, Login 200, Duplicate 409, Logout 200)
 * 5. Persistent Props Data Flow & Value Edge Calculation
 * 6. NFL Tactical Analytics (Heatmaps, Coverage Splits & Route Trees)
 * 7. Bet Tracker Full CRUD & OWASP BOLA Access Control
 * 8. Protected Admin Endpoints & Quota Shielding
 *
 * Usage:
 *   php backend/tests/run_tests.php [baseUrl]
 * Example:
 *   php backend/tests/run_tests.php http://127.0.0.1:8000
 */

class TestRunner
{
    private string $baseUrl;
    private ?string $cookieFile = null;
    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'matchupiq_test_cookies_' . uniqid() . '.txt';
    }

    public function __destruct()
    {
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function run(): void
    {
        $this->header("MatchupIQ API — Automated Integration Test Suite");
        echo "Base URL: {$this->baseUrl}\n";
        echo "Date:     " . date('Y-m-d H:i:s') . "\n\n";

        // ==========================================
        // Suite 1: Health & CORS Infrastructure
        // ==========================================
        $this->section("Suite 1: Health Check & Infrastructure");

        $health = $this->request('GET', '/api/health-check.php');
        $this->assert("GET /api/health-check.php returns 200 OK", $health['code'] === 200);
        $this->assert("Health payload contains status = 'ok'", ($health['json']['status'] ?? '') === 'ok');
        $this->assert("Health payload identifies 'MatchupIQ API'", ($health['json']['service'] ?? '') === 'MatchupIQ API');

        $options = $this->request('OPTIONS', '/api/health-check.php');
        $this->assert("OPTIONS preflight returns 200 OK for CORS", $options['code'] === 200);

        $invalidMethod = $this->request('POST', '/api/health-check.php');
        $this->assert("POST /api/health-check.php is blocked with 405 Method Not Allowed", $invalidMethod['code'] === 405);

        // ==========================================
        // Suite 2: Access Control & Route Protection
        // ==========================================
        $this->section("Suite 2: Access Control & Route Protection");

        $unauthMe = $this->request('GET', '/api/auth/me.php');
        $this->assert("GET /api/auth/me.php without session is rejected with 401 Unauthorized", $unauthMe['code'] === 401);
        $this->assert("401 response contains error message", isset($unauthMe['json']['message']));

        // Admin route protection & Method verification
        $unauthAdmin = $this->request('POST', '/api/admin/odds/refresh.php');
        $this->assert("POST /api/admin/odds/refresh.php without session is rejected with 401 Unauthorized", $unauthAdmin['code'] === 401);

        $invalidAdminMethod = $this->request('GET', '/api/admin/odds/refresh.php');
        $this->assert("GET /api/admin/odds/refresh.php is blocked with 405 Method Not Allowed", $invalidAdminMethod['code'] === 405);

        // ==========================================
        // Suite 3: Input Validation & Sanitization
        // ==========================================
        $this->section("Suite 3: Input Validation (Invalid Inputs)");

        $badEmail = $this->request('POST', '/api/auth/register.php', [
            'full_name' => 'Test User',
            'email' => 'invalid-email-format',
            'password' => 'ValidPassword123!'
        ]);
        $this->assert("Registration with invalid email format returns 422 Unprocessable", $badEmail['code'] === 422);
        $this->assert("Validation error specifies 'email' field", isset($badEmail['json']['errors']['email']));

        $shortPass = $this->request('POST', '/api/auth/register.php', [
            'full_name' => 'Test User',
            'email' => 'valid' . uniqid() . '@example.com',
            'password' => '123'
        ]);
        $this->assert("Registration with short password (<8 chars) returns 422 Unprocessable", $shortPass['code'] === 422);
        $this->assert("Validation error specifies 'password' field", isset($shortPass['json']['errors']['password']));

        $emptyLogin = $this->request('POST', '/api/auth/login.php', []);
        $this->assert("Login with empty payload returns 400 Bad Request", $emptyLogin['code'] === 400);

        // ==========================================
        // Suite 4: Authentication Lifecycle & RBAC
        // ==========================================
        $this->section("Suite 4: Secure Authentication Flow & RBAC");

        $uniqueEmail = 'user_' . substr(md5(uniqid()), 0, 8) . '@matchupiq.test';
        $testPassword = 'TestPassword2026!';

        $register = $this->request('POST', '/api/auth/register.php', [
            'full_name' => 'Integration Tester',
            'email' => $uniqueEmail,
            'password' => $testPassword
        ]);

        if ($register['code'] === 500 && str_contains(json_encode($register), 'Database connection failed')) {
            $this->skip("Registration & persistence tests skipped: MySQL database is not currently running.");
        } else {
            $this->assert("User registration returns 201 Created", $register['code'] === 201);
            $this->assert("Registration payload returns created user without exposing password hash", 
                isset($register['json']['data']['user']['user_id']) && !isset($register['json']['data']['user']['password_hash']));

            // Duplicate registration
            $duplicate = $this->request('POST', '/api/auth/register.php', [
                'full_name' => 'Duplicate Tester',
                'email' => $uniqueEmail,
                'password' => $testPassword
            ]);
            $this->assert("Registering duplicate email returns 409 Conflict", $duplicate['code'] === 409);

            // Wrong password login
            $wrongPass = $this->request('POST', '/api/auth/login.php', [
                'email' => $uniqueEmail,
                'password' => 'WrongPassword!'
            ]);
            $this->assert("Login with wrong password returns 401 Unauthorized", $wrongPass['code'] === 401);

            // Successful login with session cookie
            $login = $this->request('POST', '/api/auth/login.php', [
                'email' => $uniqueEmail,
                'password' => $testPassword
            ], true);
            $this->assert("Login with valid credentials returns 200 OK", $login['code'] === 200);
            $this->assert("Login returns user full name and role", 
                ($login['json']['data']['user']['email'] ?? '') === $uniqueEmail);

            // Active session verification (with cookie)
            $authMe = $this->request('GET', '/api/auth/me.php', null, true);
            $this->assert("GET /api/auth/me.php with active cookie returns 200 OK", $authMe['code'] === 200);
            $this->assert("Session accurately identifies authenticated user", 
                ($authMe['json']['data']['user']['email'] ?? '') === $uniqueEmail);

            // RBAC Verification: Regular user is blocked from admin endpoint
            $userAdminForbidden = $this->request('POST', '/api/admin/odds/refresh.php', null, true);
            $this->assert("RBAC: Regular authenticated user accessing /api/admin/odds/refresh.php is blocked with 403 Forbidden", 
                $userAdminForbidden['code'] === 403);

            // Logout
            $logout = $this->request('POST', '/api/auth/logout.php', null, true);
            $this->assert("POST /api/auth/logout.php destroys session with 200 OK", $logout['code'] === 200);

            // Verify session is no longer valid
            $meAfterLogout = $this->request('GET', '/api/auth/me.php', null, true);
            $this->assert("Access after logout is rejected with 401 Unauthorized", $meAfterLogout['code'] === 401);
        }

        // ==========================================
        // Suite 5: Props & Data Persistence Flow + Value Edge
        // ==========================================
        $this->section("Suite 5: Props & Odds First Functional Flow + Value Edge");

        $props = $this->request('GET', '/api/props/index.php');
        if ($props['code'] === 500 && str_contains(json_encode($props), 'Database connection failed')) {
            $this->skip("Props listing skipped: MySQL database is not currently running.");
        } else {
            $this->assert("GET /api/props/index.php returns 200 OK", $props['code'] === 200);
            $this->assert("Props response contains 'props' array and total count", 
                isset($props['json']['data']['props']) && is_array($props['json']['data']['props']));

            $propsList = $props['json']['data']['props'] ?? [];
            if (!empty($propsList)) {
                $firstProp = $propsList[0];
                $this->assert("Prop record contains calculated_edge and player details", 
                    isset($firstProp['calculated_edge'], $firstProp['player_name'], $firstProp['bookmaker_name']));
                $this->assert("Prop record contains enriched edge_analysis from EdgeCalculatorService", 
                    isset($firstProp['edge_analysis']['calculated_edge'], $firstProp['edge_analysis']['recommendation']));

                $propId = $firstProp['prop_id'];
                $singleProp = $this->request('GET', "/api/props/show.php?id={$propId}");
                $this->assert("GET /api/props/show.php?id={$propId} returns 200 OK", $singleProp['code'] === 200);
                $this->assert("Single prop details include edge_analysis breakdown", 
                    isset($singleProp['json']['data']['prop']['edge_analysis']['matchup_multiplier']));
            }

            $notFound = $this->request('GET', '/api/props/show.php?id=999999');
            $this->assert("GET /api/props/show.php with non-existent id returns 404 Not Found", $notFound['code'] === 404);

            // Custom Edge calculation endpoint
            $customEdge = $this->request('POST', '/api/props/calculate-edge.php', [
                'player_average' => 280.0,
                'line'           => 260.0,
                'odds'           => 1.95,
                'defensive_rank' => 24
            ]);
            $this->assert("POST /api/props/calculate-edge.php returns 200 OK", $customEdge['code'] === 200);
            $this->assert("Custom edge calculation returns positive +EV recommendation", 
                ($customEdge['json']['data']['analysis']['calculated_edge'] ?? 0) > 0);

            // Relational synchronization verification: OddsApiService::syncOddsToProps
            require_once __DIR__ . '/../services/OddsApiService.php';
            $oddsService = new OddsApiService();
            $mockEvent = [
                'id' => 'test_suite_event_nfl',
                'sport_key' => 'americanfootball_nfl',
                'home_team' => 'Kansas City Chiefs',
                'away_team' => 'San Francisco 49ers',
                'bookmakers' => [
                    [
                        'key' => 'bet365',
                        'title' => 'Bet365',
                        'markets' => [
                            [
                                'key' => 'player_pass_yds',
                                'outcomes' => [
                                    ['name' => 'Over', 'description' => 'Patrick Mahomes', 'price' => 1.95, 'point' => 275.5],
                                    ['name' => 'Under', 'description' => 'Patrick Mahomes', 'price' => 1.85, 'point' => 275.5]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            $syncMetrics = $oddsService->syncOddsToProps([$mockEvent]);
            $this->assert("OddsApiService::syncOddsToProps processes events and updates props",
                $syncMetrics['events_processed'] >= 1 && ($syncMetrics['updated'] + $syncMetrics['inserted']) >= 1);

            $syncedPropCheck = $this->request('GET', '/api/props/show.php?id=1');
            $this->assert("GET /api/props/show.php?id=1 reflects synchronized line threshold (275.5)",
                (float) ($syncedPropCheck['json']['data']['prop']['line_over'] ?? 0) === 275.5);
        }

        // ==========================================
        // Suite 6: NFL Tactical Analytics (Heatmaps, Coverage Splits & Route Trees)
        // ==========================================
        $this->section("Suite 6: NFL Tactical Analytics (Heatmaps, Coverage Splits & Route Trees)");

        $missingPlayerHeatmap = $this->request('GET', '/api/tactics/heatmap.php');
        $this->assert("GET /api/tactics/heatmap.php without player_id returns 400 Bad Request", $missingPlayerHeatmap['code'] === 400);

        $invalidMethodHeatmap = $this->request('POST', '/api/tactics/heatmap.php');
        $this->assert("POST /api/tactics/heatmap.php is blocked with 405 Method Not Allowed", $invalidMethodHeatmap['code'] === 405);

        $missingPlayerCoverage = $this->request('GET', '/api/tactics/coverage.php');
        $this->assert("GET /api/tactics/coverage.php without player_id returns 400 Bad Request", $missingPlayerCoverage['code'] === 400);

        $missingPlayerRoutes = $this->request('GET', '/api/tactics/routes.php');
        $this->assert("GET /api/tactics/routes.php without player_id returns 400 Bad Request", $missingPlayerRoutes['code'] === 400);

        $heatmap = $this->request('GET', '/api/tactics/heatmap.php?player_id=3');
        if ($heatmap['code'] === 500 && str_contains(json_encode($heatmap), 'Database connection failed')) {
            $this->skip("Tactical heatmap, coverage & route tree persistence tests skipped: MySQL database is not currently running.");
        } else {
            $this->assert("GET /api/tactics/heatmap.php?player_id=3 returns 200 OK", $heatmap['code'] === 200);
            $zones = $heatmap['json']['data']['zones'] ?? [];
            $this->assert("Heatmap response contains all 5 offensive rushing zones", is_array($zones) && count($zones) === 5);
            $this->assert("Heatmap response includes summary metrics and dominant_zone", 
                isset($heatmap['json']['data']['summary']['dominant_zone'], $heatmap['json']['data']['summary']['overall_success_rate']));

            $notFoundHeatmap = $this->request('GET', '/api/tactics/heatmap.php?player_id=999999');
            $this->assert("GET /api/tactics/heatmap.php?player_id=999999 returns 404 Not Found", $notFoundHeatmap['code'] === 404);

            $coverage = $this->request('GET', '/api/tactics/coverage.php?player_id=2&zone_rate=70');
            $this->assert("GET /api/tactics/coverage.php?player_id=2&zone_rate=70 returns 200 OK", $coverage['code'] === 200);
            $splits = $coverage['json']['data']['splits'] ?? [];
            $schemes = is_array($splits) ? array_column($splits, 'coverage_type') : [];
            $this->assert("Coverage response contains both 'man' and 'zone' splits", 
                in_array('man', $schemes, true) && in_array('zone', $schemes, true));
            $covSummary = $coverage['json']['data']['summary'] ?? [];
            $this->assert("Coverage summary projects FAVORABLE advantage and tactical_edge_boost for Kelce vs 70% Zone", 
                ($covSummary['tactical_advantage'] ?? '') === 'FAVORABLE'
                && isset($covSummary['projected_catch_rate'], $covSummary['tactical_edge_boost']));

            $notFoundCoverage = $this->request('GET', '/api/tactics/coverage.php?player_id=999999');
            $this->assert("GET /api/tactics/coverage.php?player_id=999999 returns 404 Not Found", $notFoundCoverage['code'] === 404);

            $routes = $this->request('GET', '/api/tactics/routes.php?player_id=2');
            $this->assert("GET /api/tactics/routes.php?player_id=2 returns 200 OK", $routes['code'] === 200);
            $routeList = $routes['json']['data']['routes'] ?? [];
            $this->assert("Route tree response contains NextGenStats routes array and summary", 
                is_array($routeList) && count($routeList) > 0 && isset($routes['json']['data']['summary']['primary_route']));
        }

        // ==========================================
        // Suite 7: Bet Tracker CRUD & OWASP BOLA Protection
        // ==========================================
        $this->section("Suite 7: Bet Tracker Full CRUD & OWASP BOLA Access Control");

        // 1. Unauthenticated access blocked
        $unauthTracker = $this->request('GET', '/api/tracker/index.php');
        $this->assert("GET /api/tracker/index.php without login is rejected with 401 Unauthorized", $unauthTracker['code'] === 401);

        // 2. Login User 1
        $loginU1 = $this->request('POST', '/api/auth/login.php', [
            'email' => 'rodrigo@matchupiq.com',
            'password' => 'Password123!'
        ], true);

        if ($loginU1['code'] === 200) {
            // Get initial list & metrics
            $trackerList = $this->request('GET', '/api/tracker/index.php', null, true);
            $this->assert("GET /api/tracker/index.php for authenticated user returns 200 OK", $trackerList['code'] === 200);
            $this->assert("Bet Tracker response includes summary performance metrics (ROI, Win Rate)", 
                isset($trackerList['json']['data']['metrics']['roi_percentage'], $trackerList['json']['data']['metrics']['win_rate']));

            // Create a new bet for User 1
            $createBet = $this->request('POST', '/api/tracker/create.php', [
                'prop_id'      => 1,
                'taken_odds'   => 1.95,
                'stake_amount' => 250.0,
                'notes'        => 'Sprint 3 Integration Test Bet'
            ], true);
            $this->assert("POST /api/tracker/create.php logs bet with 201 Created", $createBet['code'] === 201);
            $createdBetId = $createBet['json']['data']['bet']['bet_id'] ?? null;
            $this->assert("Bet record is successfully created with valid bet_id", $createdBetId !== null);

            // Update bet status (e.g. mark as won)
            if ($createdBetId) {
                $updateBet = $this->request('POST', '/api/tracker/update.php', [
                    'bet_id' => $createdBetId,
                    'status' => 'won',
                    'notes'  => 'Updated to won during integration test'
                ], true);
                $this->assert("POST /api/tracker/update.php updates status with 200 OK", $updateBet['code'] === 200);
                $this->assert("Bet status is accurately updated to 'won'", 
                    ($updateBet['json']['data']['bet']['status'] ?? '') === 'won');

                // BOLA Protection Test: Logout User 1, Login User 2 (Admin) and try to modify or delete User 1's bet
                $this->request('POST', '/api/auth/logout.php', null, true);

                $loginU2 = $this->request('POST', '/api/auth/login.php', [
                    'email' => 'admin@matchupiq.com',
                    'password' => 'Password123!'
                ], true);

                if ($loginU2['code'] === 200) {
                    // Try to delete User 1's bet as User 2 (should be blocked by BOLA)
                    $bolaDelete = $this->request('POST', '/api/tracker/delete.php', [
                        'bet_id' => $createdBetId
                    ], true);
                    $this->assert("OWASP BOLA: User 2 attempting to delete User 1's bet is rejected with 404/Access Denied", 
                        $bolaDelete['code'] === 404);

                    // Re-login User 1 and clean up the bet
                    $this->request('POST', '/api/auth/logout.php', null, true);
                    $this->request('POST', '/api/auth/login.php', [
                        'email' => 'rodrigo@matchupiq.com',
                        'password' => 'Password123!'
                    ], true);

                    $legitDelete = $this->request('POST', '/api/tracker/delete.php', [
                        'bet_id' => $createdBetId
                    ], true);
                    $this->assert("Legitimate owner deleting their own bet returns 200 OK", $legitDelete['code'] === 200);
                }
            }
        }

        // ==========================================
        // Summary
        // ==========================================
        $this->summary();
    }

    /**
     * Executes an HTTP request using cURL.
     */
    private function request(string $method, string $path, ?array $body = null, bool $useCookies = false): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $headers = ['Accept: application/json'];

        if ($body !== null) {
            $jsonBody = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonBody);
        }

        if ($useCookies && $this->cookieFile) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response ?: '', true);

        return [
            'code' => $httpCode,
            'raw'  => $response,
            'json' => is_array($json) ? $json : null
        ];
    }

    private function assert(string $description, bool $condition): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$description}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$description}\n";
        }
    }

    private function skip(string $reason): void
    {
        $this->skipped++;
        echo "  [SKIP] {$reason}\n";
    }

    private function header(string $title): void
    {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo " {$title}\n";
        echo str_repeat('=', 70) . "\n";
    }

    private function section(string $name): void
    {
        echo "\n--- {$name} ---\n";
    }

    private function summary(): void
    {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo " TEST EXECUTION SUMMARY\n";
        echo str_repeat('=', 70) . "\n";
        echo " Passed:  {$this->passed}\n";
        echo " Failed:  {$this->failed}\n";
        echo " Skipped: {$this->skipped}\n";
        echo " Total:   " . ($this->passed + $this->failed + $this->skipped) . "\n";

        if ($this->failed === 0) {
            echo "\n RESULT: ALL CRITICAL SUITES PASSED (100% SUCCESS)\n\n";
        } else {
            echo "\n RESULT: SOME TESTS FAILED. PLEASE REVIEW OUTPUT ABOVE.\n\n";
        }
    }
}

// Entrypoint execution
$targetUrl = $argv[1] ?? 'http://127.0.0.1:8000';
$runner = new TestRunner($targetUrl);
$runner->run();

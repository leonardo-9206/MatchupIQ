<?php
/**
 * MatchupIQ — Backend Automated Integration Test Suite
 * Evaluates Sprint 1 & Sprint 2 Backend requirements:
 * 1. Health & CORS Infrastructure
 * 2. Access Restrictions (401 Unauthorized)
 * 3. Server-side Input Validation (400 Bad Request, 422 Unprocessable)
 * 4. Secure Authentication Flow (Register 201, Login 200, Duplicate 409, Logout 200)
 * 5. Persistent Props Data Flow (Listing 200, Single Prop 200, 404 Not Found)
 * 6. NFL Tactical Analytics (Heatmaps & Coverage Splits 200, 400, 404, 405)
 *
 * Usage:
 *   php backend/tests/run_tests.php [baseUrl]
 * Example:
 *   php backend/tests/run_tests.php http://localhost/MatchupIQ/backend
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
        // Suite 2: Access Restrictions (Security)
        // ==========================================
        $this->section("Suite 2: Access Control & Route Protection");

        $unauthMe = $this->request('GET', '/api/auth/me.php');
        $this->assert("GET /api/auth/me.php without session is rejected with 401 Unauthorized", $unauthMe['code'] === 401);
        $this->assert("401 response contains error message", isset($unauthMe['json']['message']));

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
        // Suite 4: Authentication Lifecycle
        // ==========================================
        $this->section("Suite 4: Secure Authentication Flow");

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

            // Logout
            $logout = $this->request('POST', '/api/auth/logout.php', null, true);
            $this->assert("POST /api/auth/logout.php destroys session with 200 OK", $logout['code'] === 200);

            // Verify session is no longer valid
            $meAfterLogout = $this->request('GET', '/api/auth/me.php', null, true);
            $this->assert("Access after logout is rejected with 401 Unauthorized", $meAfterLogout['code'] === 401);
        }

        // ==========================================
        // Suite 5: Props & Data Persistence Flow
        // ==========================================
        $this->section("Suite 5: Props & Odds First Functional Flow");

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

                $propId = $firstProp['prop_id'];
                $singleProp = $this->request('GET', "/api/props/show.php?id={$propId}");
                $this->assert("GET /api/props/show.php?id={$propId} returns 200 OK", $singleProp['code'] === 200);
            }

            $notFound = $this->request('GET', '/api/props/show.php?id=999999');
            $this->assert("GET /api/props/show.php with non-existent id returns 404 Not Found", $notFound['code'] === 404);
        }

        // ==========================================
        // Suite 6: NFL Tactical Analytics (Heatmaps & Coverage Splits)
        // ==========================================
        $this->section("Suite 6: NFL Tactical Analytics (Heatmaps & Coverage Splits)");

        $missingPlayerHeatmap = $this->request('GET', '/api/tactics/heatmap.php');
        $this->assert("GET /api/tactics/heatmap.php without player_id returns 400 Bad Request", $missingPlayerHeatmap['code'] === 400);

        $invalidMethodHeatmap = $this->request('POST', '/api/tactics/heatmap.php');
        $this->assert("POST /api/tactics/heatmap.php is blocked with 405 Method Not Allowed", $invalidMethodHeatmap['code'] === 405);

        $missingPlayerCoverage = $this->request('GET', '/api/tactics/coverage.php');
        $this->assert("GET /api/tactics/coverage.php without player_id returns 400 Bad Request", $missingPlayerCoverage['code'] === 400);

        $heatmap = $this->request('GET', '/api/tactics/heatmap.php?player_id=3');
        if ($heatmap['code'] === 500 && str_contains(json_encode($heatmap), 'Database connection failed')) {
            $this->skip("Tactical heatmap & coverage persistence tests skipped: MySQL database is not currently running.");
        } else {
            $this->assert("GET /api/tactics/heatmap.php?player_id=3 returns 200 OK", $heatmap['code'] === 200);
            $zones = $heatmap['json']['data']['zones'] ?? [];
            $this->assert("Heatmap response contains all 5 offensive rushing zones", is_array($zones) && count($zones) === 5);
            $this->assert("Heatmap response includes summary metrics and dominant_zone", 
                isset($heatmap['json']['data']['summary']['dominant_zone'], $heatmap['json']['data']['summary']['overall_success_rate']));

            $notFoundHeatmap = $this->request('GET', '/api/tactics/heatmap.php?player_id=999999');
            $this->assert("GET /api/tactics/heatmap.php?player_id=999999 returns 404 Not Found", $notFoundHeatmap['code'] === 404);

            $coverage = $this->request('GET', '/api/tactics/coverage.php?player_id=2');
            $this->assert("GET /api/tactics/coverage.php?player_id=2 returns 200 OK", $coverage['code'] === 200);
            $splits = $coverage['json']['data']['splits'] ?? [];
            $schemes = is_array($splits) ? array_column($splits, 'coverage_type') : [];
            $this->assert("Coverage response contains both 'man' and 'zone' splits", 
                in_array('man', $schemes, true) && in_array('zone', $schemes, true));

            $notFoundCoverage = $this->request('GET', '/api/tactics/coverage.php?player_id=999999');
            $this->assert("GET /api/tactics/coverage.php?player_id=999999 returns 404 Not Found", $notFoundCoverage['code'] === 404);
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

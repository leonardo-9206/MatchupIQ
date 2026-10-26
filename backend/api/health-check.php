<?php
/**
 * Health Check Endpoint
 * Route: GET /api/health-check.php
 * Used to verify server availability, CORS configuration, and API responsiveness.
 */

require_once __DIR__ . '/../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Response.php';

// Handle CORS headers and preflight checks
CorsMiddleware::handle();

// Ensure only GET requests are allowed
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
    Response::error('Method Not Allowed. Only GET requests are accepted.', 405);
}

// Check database connectivity
$dbStatus = 'disconnected';
try {
    $db = Database::getInstance()->getConnection();
    if ($db) {
        $dbStatus = 'connected';
    }
} catch (Throwable $e) {
    $dbStatus = 'error: ' . $e->getMessage();
}

Response::json([
    'status' => 'ok',
    'service' => 'MatchupIQ API',
    'version' => '1.0.0',
    'database' => $dbStatus,
    'timestamp' => date('c')
], 200);

<?php
/**
 * Admin System & Database Diagnostics Endpoint
 * Route: GET /api/admin/stats.php
 * Access: Administrator role only (HTTP 401 Unauthorized / HTTP 403 Forbidden)
 */

require_once __DIR__ . '/../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../config/Response.php';

// Handle CORS headers and preflight checks
CorsMiddleware::handle();

// Ensure only GET requests are allowed
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
    Response::error('Method Not Allowed. Only GET requests are accepted.', 405);
}

$controller = new AdminController();
$controller->getDatabaseStats();

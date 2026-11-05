<?php
/**
 * Admin ESPN Ingestion Endpoint
 * Route: POST /api/admin/espn/sync.php
 * Access: Administrator role only (HTTP 401 Unauthorized / HTTP 403 Forbidden)
 */

require_once __DIR__ . '/../../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../../controllers/AdminController.php';
require_once __DIR__ . '/../../../config/Response.php';

CorsMiddleware::handle();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    Response::error('Method Not Allowed. Only POST requests are accepted.', 405);
}

$controller = new AdminController();
$controller->syncEspnData();

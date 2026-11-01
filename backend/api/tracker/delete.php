<?php
/**
 * Bet Tracker Delete Endpoint
 * Route: POST or DELETE /api/tracker/delete.php
 */

require_once __DIR__ . '/../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../controllers/TrackerController.php';
require_once __DIR__ . '/../../config/Response.php';

CorsMiddleware::handle();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST' && $method !== 'DELETE') {
    Response::error('Method Not Allowed. Only POST or DELETE requests are accepted.', 405);
}

$controller = new TrackerController();
$controller->delete();

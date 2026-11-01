<?php
/**
 * Bet Tracker Update Endpoint
 * Route: POST or PUT /api/tracker/update.php
 */

require_once __DIR__ . '/../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../controllers/TrackerController.php';
require_once __DIR__ . '/../../config/Response.php';

CorsMiddleware::handle();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST' && $method !== 'PUT') {
    Response::error('Method Not Allowed. Only POST or PUT requests are accepted.', 405);
}

$controller = new TrackerController();
$controller->update();

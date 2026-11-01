<?php
/**
 * Bet Tracker Create Endpoint
 * Route: POST /api/tracker/create.php
 */

require_once __DIR__ . '/../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../controllers/TrackerController.php';
require_once __DIR__ . '/../../config/Response.php';

CorsMiddleware::handle();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    Response::error('Method Not Allowed. Only POST requests are accepted.', 405);
}

$controller = new TrackerController();
$controller->create();

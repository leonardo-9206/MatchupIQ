<?php
/**
 * NFL Defensive Coverage Splits API Endpoint
 * Route: GET /api/tactics/coverage.php?player_id=2
 */

require_once __DIR__ . '/../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../controllers/TacticsController.php';
require_once __DIR__ . '/../../config/Response.php';

CorsMiddleware::handle();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
    Response::error('Method Not Allowed. Only GET requests are accepted.', 405);
}

$controller = new TacticsController();
$controller->coverage();

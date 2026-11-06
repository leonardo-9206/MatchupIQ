<?php
/**
 * NFL Rushing Zone Heatmap API Endpoint
 * Route: GET /api/tactics/heatmap.php?player_id=3
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
$controller->heatmap();

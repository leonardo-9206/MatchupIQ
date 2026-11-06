<?php
/**
 * Single Prop Detail API Endpoint
 * Route: GET /api/props/show.php?id=1
 */

require_once __DIR__ . '/../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../controllers/PropsController.php';
require_once __DIR__ . '/../../config/Response.php';

CorsMiddleware::handle();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
    Response::error('Method Not Allowed. Only GET requests are accepted.', 405);
}

$controller = new PropsController();
$controller->show();

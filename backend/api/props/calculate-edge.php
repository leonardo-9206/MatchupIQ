<?php
/**
 * Custom Value Edge Calculation Endpoint
 * Route: POST /api/props/calculate-edge.php
 */

require_once __DIR__ . '/../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../controllers/PropsController.php';
require_once __DIR__ . '/../../config/Response.php';

CorsMiddleware::handle();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    Response::error('Method Not Allowed. Only POST requests are accepted.', 405);
}

$controller = new PropsController();
$controller->calculateCustomEdge();

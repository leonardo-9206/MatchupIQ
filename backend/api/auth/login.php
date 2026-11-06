<?php
/**
 * Login API Endpoint
 * Route: POST /api/auth/login.php
 */

require_once __DIR__ . '/../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../config/Response.php';

CorsMiddleware::handle();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    Response::error('Method Not Allowed. Only POST requests are accepted.', 405);
}

$controller = new AuthController();
$controller->login();

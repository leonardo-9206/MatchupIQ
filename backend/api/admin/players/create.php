<?php
/**
 * Admin Players Catalog Create Endpoint
 * Route: POST /api/admin/players/create.php
 */

require_once __DIR__ . '/../../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../../controllers/AdminController.php';
require_once __DIR__ . '/../../../config/Response.php';

CorsMiddleware::handle();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    Response::error('Method Not Allowed. Only POST requests are accepted.', 405);
}

(new AdminController())->createPlayer();

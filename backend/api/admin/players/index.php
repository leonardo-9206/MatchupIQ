<?php
/**
 * Admin Players Catalog List Endpoint
 * Route: GET /api/admin/players/index.php
 */

require_once __DIR__ . '/../../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../../controllers/AdminController.php';
require_once __DIR__ . '/../../../config/Response.php';

CorsMiddleware::handle();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    Response::error('Method Not Allowed. Only GET requests are accepted.', 405);
}

(new AdminController())->listPlayers();

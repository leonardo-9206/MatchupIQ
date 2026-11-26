<?php
/**
 * Admin Players Catalog Update Endpoint
 * Route: PUT /api/admin/players/update.php
 */

require_once __DIR__ . '/../../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../../controllers/AdminController.php';
require_once __DIR__ . '/../../../config/Response.php';

CorsMiddleware::handle();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'PUT') {
    Response::error('Method Not Allowed. Only PUT requests are accepted.', 405);
}

(new AdminController())->updatePlayer();

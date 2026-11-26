<?php
/**
 * Admin Players Catalog Delete Endpoint
 * Route: DELETE /api/admin/players/delete.php?id=1
 */

require_once __DIR__ . '/../../../middlewares/CorsMiddleware.php';
require_once __DIR__ . '/../../../controllers/AdminController.php';
require_once __DIR__ . '/../../../config/Response.php';

CorsMiddleware::handle();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'DELETE') {
    Response::error('Method Not Allowed. Only DELETE requests are accepted.', 405);
}

(new AdminController())->deletePlayer();

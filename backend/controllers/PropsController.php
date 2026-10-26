<?php
/**
 * PropsController
 * Handles request orchestration and business logic for player props and odds.
 */

require_once __DIR__ . '/../models/Prop.php';
require_once __DIR__ . '/../config/Response.php';

class PropsController
{
    private Prop $propModel;

    public function __construct()
    {
        $this->propModel = new Prop();
    }

    /**
     * Lists all props with optional sport filtering and pagination limits.
     * GET /api/props/index.php?sport=nfl&limit=20
     */
    public function index(): void
    {
        try {
            $sport = isset($_GET['sport']) && is_string($_GET['sport']) ? trim($_GET['sport']) : null;
            $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 50;

            $props = $this->propModel->getAll($sport, $limit);

            Response::success('Props retrieved successfully.', [
                'total' => count($props),
                'filter' => [
                    'sport' => $sport,
                    'limit' => $limit
                ],
                'props' => $props
            ], 200);
        } catch (Throwable $e) {
            error_log('Props Controller Error: ' . $e->getMessage());
            Response::error('Failed to retrieve props. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Displays a single prop by ID.
     * GET /api/props/show.php?id=1
     */
    public function show(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            Response::error('A valid integer "id" parameter is required.', 400);
        }

        try {
            $prop = $this->propModel->findById($id);

            if (!$prop) {
                Response::error('Prop not found.', 404);
            }

            Response::success('Prop retrieved successfully.', ['prop' => $prop], 200);
        } catch (Throwable $e) {
            error_log('Props Controller Show Error: ' . $e->getMessage());
            Response::error('Failed to retrieve prop details.', 500);
        }
    }
}

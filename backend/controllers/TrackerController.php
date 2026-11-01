<?php
/**
 * TrackerController
 * Orchestrates Bet Tracker operations: list, create, update, delete, and performance metrics.
 * Uses Validator for input sanitization and AuthMiddleware for strict session and BOLA protection.
 */

require_once __DIR__ . '/../models/BetTracker.php';
require_once __DIR__ . '/../models/Prop.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../middlewares/Validator.php';
require_once __DIR__ . '/../config/Response.php';

class TrackerController
{
    private BetTracker $trackerModel;
    private Prop $propModel;

    public function __construct()
    {
        $this->trackerModel = new BetTracker();
        $this->propModel = new Prop();
    }

    /**
     * Lists user bets and summary performance metrics.
     * GET /api/tracker/index.php?status=pending
     */
    public function index(): void
    {
        // Enforce active authenticated session
        $currentUser = AuthMiddleware::requireAuth();
        $userId = (int) $currentUser['user_id'];

        $status = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : null;

        try {
            $bets = $this->trackerModel->getByUserId($userId, $status);
            $metrics = $this->trackerModel->getUserPerformanceMetrics($userId);

            Response::success('User bets and metrics retrieved successfully.', [
                'metrics' => $metrics,
                'total'   => count($bets),
                'bets'    => $bets
            ], 200);
        } catch (Throwable $e) {
            error_log('Tracker Controller Index Error: ' . $e->getMessage());
            Response::error('Failed to retrieve bet history.', 500);
        }
    }

    /**
     * Logs a new bet for the authenticated user.
     * POST /api/tracker/create.php
     */
    public function create(): void
    {
        $currentUser = AuthMiddleware::requireAuth();
        $userId = (int) $currentUser['user_id'];

        $raw = file_get_contents('php://input');
        $input = json_decode($raw ?: '', true) ?: [];

        $validator = Validator::make($input, [
            'prop_id'      => 'required|integer|min:1',
            'taken_odds'   => 'required|numeric|min:1.01',
            'stake_amount' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            Response::error('Validation failed for bet creation.', 422, $validator->errors());
        }

        $validated = $validator->validated();
        $propId = (int) $validated['prop_id'];
        $takenOdds = (float) $validated['taken_odds'];
        $stakeAmount = (float) $validated['stake_amount'];
        $notes = isset($input['notes']) && is_string($input['notes']) ? trim($input['notes']) : null;

        // Verify that referenced prop exists
        $prop = $this->propModel->findById($propId);
        if (!$prop) {
            Response::error('Referenced prop does not exist.', 404);
        }

        try {
            $betId = $this->trackerModel->create($userId, $propId, $takenOdds, $stakeAmount, $notes);
            $createdBet = $this->trackerModel->findByIdAndUser($betId, $userId);

            Response::success('Bet logged successfully.', ['bet' => $createdBet], 201);
        } catch (Throwable $e) {
            error_log('Tracker Controller Create Error: ' . $e->getMessage());
            Response::error('Failed to log bet.', 500);
        }
    }

    /**
     * Updates an existing bet's status or notes (Protected against BOLA).
     * POST or PUT /api/tracker/update.php
     */
    public function update(): void
    {
        $currentUser = AuthMiddleware::requireAuth();
        $userId = (int) $currentUser['user_id'];

        $raw = file_get_contents('php://input');
        $input = json_decode($raw ?: '', true) ?: [];

        $validator = Validator::make($input, [
            'bet_id' => 'required|integer|min:1',
            'status' => 'required|in:pending,won,lost,void'
        ]);

        if ($validator->fails()) {
            Response::error('Validation failed for bet update.', 422, $validator->errors());
        }

        $validated = $validator->validated();
        $betId = (int) $validated['bet_id'];
        $status = (string) $validated['status'];
        $notes = isset($input['notes']) && is_string($input['notes']) ? trim($input['notes']) : null;

        // Verify bet ownership before update
        $existing = $this->trackerModel->findByIdAndUser($betId, $userId);
        if (!$existing) {
            Response::error('Bet not found or access denied (BOLA protection).', 404);
        }

        try {
            $this->trackerModel->update($betId, $userId, $status, $notes);
            $updatedBet = $this->trackerModel->findByIdAndUser($betId, $userId);

            Response::success('Bet updated successfully.', ['bet' => $updatedBet], 200);
        } catch (Throwable $e) {
            error_log('Tracker Controller Update Error: ' . $e->getMessage());
            Response::error('Failed to update bet.', 500);
        }
    }

    /**
     * Deletes a bet record (Protected against BOLA).
     * POST or DELETE /api/tracker/delete.php
     */
    public function delete(): void
    {
        $currentUser = AuthMiddleware::requireAuth();
        $userId = (int) $currentUser['user_id'];

        $raw = file_get_contents('php://input');
        $input = json_decode($raw ?: '', true) ?: [];

        $betId = filter_var($input['bet_id'] ?? $_GET['bet_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$betId) {
            Response::error('A valid integer "bet_id" is required.', 400);
        }

        // Verify bet ownership
        $existing = $this->trackerModel->findByIdAndUser($betId, $userId);
        if (!$existing) {
            Response::error('Bet not found or access denied (BOLA protection).', 404);
        }

        try {
            $this->trackerModel->delete($betId, $userId);
            Response::success('Bet removed successfully.', null, 200);
        } catch (Throwable $e) {
            error_log('Tracker Controller Delete Error: ' . $e->getMessage());
            Response::error('Failed to delete bet.', 500);
        }
    }
}

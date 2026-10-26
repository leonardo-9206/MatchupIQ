<?php
/**
 * TacticsController
 * Handles request validation and orchestrates NFL tactical analytics endpoints
 * for rushing zone heatmaps and defensive coverage splits.
 */

require_once __DIR__ . '/../models/Heatmap.php';
require_once __DIR__ . '/../models/Coverage.php';
require_once __DIR__ . '/../services/HeatmapEngine.php';
require_once __DIR__ . '/../config/Response.php';

class TacticsController
{
    private HeatmapEngine $heatmapEngine;

    public function __construct()
    {
        $this->heatmapEngine = new HeatmapEngine();
    }

    /**
     * Returns the 5-zone rushing heatmap breakdown for a given NFL player.
     * GET /api/tactics/heatmap.php?player_id=3
     */
    public function heatmap(): void
    {
        $playerId = filter_input(INPUT_GET, 'player_id', FILTER_VALIDATE_INT);

        if ($playerId === false || $playerId === null || $playerId <= 0) {
            Response::error('A valid positive integer "player_id" parameter is required.', 400);
        }

        try {
            $heatmapModel = new Heatmap();

            $player = $heatmapModel->getPlayerById($playerId);
            if (!$player) {
                Response::error('Player not found.', 404);
            }

            $zones = $heatmapModel->getZonesByPlayerId($playerId);
            if (empty($zones)) {
                Response::error('No tactical heatmap data found for the specified player.', 404);
            }

            $breakdown = $this->heatmapEngine->buildHeatmapBreakdown($player, $zones);

            Response::success('Player heatmap data retrieved successfully.', $breakdown, 200);
        } catch (Throwable $e) {
            error_log('TacticsController Heatmap Error: ' . $e->getMessage());
            Response::error('Failed to retrieve player heatmap data. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Returns the Man vs Zone coverage performance breakdown for a given NFL player.
     * GET /api/tactics/coverage.php?player_id=2
     */
    public function coverage(): void
    {
        $playerId = filter_input(INPUT_GET, 'player_id', FILTER_VALIDATE_INT);

        if ($playerId === false || $playerId === null || $playerId <= 0) {
            Response::error('A valid positive integer "player_id" parameter is required.', 400);
        }

        try {
            $heatmapModel = new Heatmap();
            $coverageModel = new Coverage();

            $player = $heatmapModel->getPlayerById($playerId);
            if (!$player) {
                Response::error('Player not found.', 404);
            }

            $splits = $coverageModel->getSplitsByPlayerId($playerId);
            if (empty($splits)) {
                Response::error('No coverage split data found for the specified player.', 404);
            }

            $breakdown = $this->heatmapEngine->buildCoverageBreakdown($player, $splits);

            Response::success('Player coverage splits retrieved successfully.', $breakdown, 200);
        } catch (Throwable $e) {
            error_log('TacticsController Coverage Error: ' . $e->getMessage());
            Response::error('Failed to retrieve player coverage splits. ' . $e->getMessage(), 500);
        }
    }
}

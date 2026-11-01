<?php
/**
 * PropsController
 * Handles request orchestration and business logic for player props, odds,
 * and statistical Value Edge breakdown calculation.
 */

require_once __DIR__ . '/../models/Prop.php';
require_once __DIR__ . '/../services/EdgeCalculatorService.php';
require_once __DIR__ . '/../middlewares/Validator.php';
require_once __DIR__ . '/../config/Response.php';

class PropsController
{
    private Prop $propModel;
    private EdgeCalculatorService $edgeCalculator;

    public function __construct()
    {
        $this->propModel = new Prop();
        $this->edgeCalculator = new EdgeCalculatorService();
    }

    /**
     * Lists all props with optional sport filtering and pagination limits.
     * Enriches each prop record with current Edge evaluation metrics.
     * GET /api/props/index.php?sport=nfl&limit=20
     */
    public function index(): void
    {
        try {
            $sport = isset($_GET['sport']) && is_string($_GET['sport']) ? trim($_GET['sport']) : null;
            $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 50;

            $props = $this->propModel->getAll($sport, $limit);

            // Enrich props with Edge recommendation badges
            $enrichedProps = array_map(function (array $prop): array {
                $playerAvg = (float) ($prop['stat_average'] ?? 0.0);
                $line = (float) ($prop['line_over'] ?? 0.0);
                $odds = (float) ($prop['odds_over'] ?? 1.90);
                $opposingRank = (int) ($prop['opposing_defensive_rank'] ?? 16);

                $edgeAnalysis = $this->edgeCalculator->calculateEdge($playerAvg, $line, $odds, $opposingRank);
                $prop['edge_analysis'] = $edgeAnalysis;

                return $prop;
            }, $props);

            Response::success('Props retrieved successfully.', [
                'total' => count($enrichedProps),
                'filter' => [
                    'sport' => $sport,
                    'limit' => $limit
                ],
                'props' => $enrichedProps
            ], 200);
        } catch (Throwable $e) {
            error_log('Props Controller Error: ' . $e->getMessage());
            Response::error('Failed to retrieve props. ' . $e->getMessage(), 500);
        }
    }

    /**
     * Displays a single prop by ID with full Edge analytical breakdown.
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

            $playerAvg = (float) ($prop['stat_average'] ?? 0.0);
            $line = (float) ($prop['line_over'] ?? 0.0);
            $odds = (float) ($prop['odds_over'] ?? 1.90);
            $opposingRank = (int) ($prop['opposing_defensive_rank'] ?? 16);

            $edgeAnalysis = $this->edgeCalculator->calculateEdge($playerAvg, $line, $odds, $opposingRank);
            $prop['edge_analysis'] = $edgeAnalysis;

            Response::success('Prop retrieved successfully.', ['prop' => $prop], 200);
        } catch (Throwable $e) {
            error_log('Props Controller Show Error: ' . $e->getMessage());
            Response::error('Failed to retrieve prop details.', 500);
        }
    }

    /**
     * On-demand calculation endpoint: computes Edge for custom arbitrary parameters.
     * POST /api/props/calculate-edge.php
     */
    public function calculateCustomEdge(): void
    {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw ?: '', true) ?: [];

        $validator = Validator::make($input, [
            'player_average' => 'required|numeric|min:0',
            'line'           => 'required|numeric|min:0.1',
            'odds'           => 'required|numeric',
            'defensive_rank' => 'integer|min:1|max:32'
        ]);

        if ($validator->fails()) {
            Response::error('Validation failed for edge calculation parameters.', 422, $validator->errors());
        }

        $validated = $validator->validated();
        $playerAvg = (float) $validated['player_average'];
        $line = (float) $validated['line'];
        $odds = (float) $validated['odds'];
        $opposingRank = isset($validated['defensive_rank']) ? (int) $validated['defensive_rank'] : 16;

        $analysis = $this->edgeCalculator->calculateEdge($playerAvg, $line, $odds, $opposingRank);

        Response::success('Value Edge calculated successfully.', ['analysis' => $analysis], 200);
    }
}

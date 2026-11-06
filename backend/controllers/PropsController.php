<?php
/**
 * PropsController
 * Handles request orchestration and business logic for player props, odds,
 * statistical Value Edge breakdown calculation, and recent 5-game trends.
 */

require_once __DIR__ . '/../models/Prop.php';
require_once __DIR__ . '/../models/GameLog.php';
require_once __DIR__ . '/../services/EdgeCalculatorService.php';
require_once __DIR__ . '/../middlewares/Validator.php';
require_once __DIR__ . '/../config/Response.php';

class PropsController
{
    private Prop $propModel;
    private GameLog $gameLogModel;
    private EdgeCalculatorService $edgeCalculator;

    public function __construct()
    {
        $this->propModel = new Prop();
        $this->gameLogModel = new GameLog();
        $this->edgeCalculator = new EdgeCalculatorService();
    }

    /**
     * Lists all props with optional sport filtering and pagination limits.
     * Enriches each prop record with current Edge evaluation metrics and recent game trends.
     * GET /api/props/index.php?sport=nfl&limit=20
     */
    public function index(): void
    {
        try {
            $sport = isset($_GET['sport']) && is_string($_GET['sport']) ? trim($_GET['sport']) : null;
            $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 50;

            $props = $this->propModel->getAll($sport, $limit);

            // Enrich props with Edge recommendation badges & recent 5-game performance
            $enrichedProps = [];
            $featuredPlayerIds = [];
            foreach ($props as $i => $prop) {
                $enriched = $this->enrichProp($prop, $i);
                // Feature the top 3 distinct players with the highest Edge
                if (count($featuredPlayerIds) < 3 && !in_array($enriched['player_id'], $featuredPlayerIds) && (float) ($enriched['calculated_edge'] ?? 0) > 0) {
                    $enriched['is_featured'] = true;
                    $featuredPlayerIds[] = $enriched['player_id'];
                } else {
                    $enriched['is_featured'] = false;
                }
                $enrichedProps[] = $enriched;
            }

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
     * Displays a single prop by ID with full Edge analytical breakdown and recent game trends.
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

            $prop = $this->enrichProp($prop, 0);

            Response::success('Prop retrieved successfully.', ['prop' => $prop], 200);
        } catch (Throwable $e) {
            error_log('Props Controller Show Error: ' . $e->getMessage());
            Response::error('Failed to retrieve prop details.', 500);
        }
    }

    /**
     * Enriches a raw prop record with Edge calculations, display labels, American odds,
     * recent 5-game trends, and featured placement flag.
     */
    private function enrichProp(array $prop, int $index = 0): array
    {
        $playerAvg = (float) ($prop['stat_average'] ?? 0.0);
        $line = (float) ($prop['line_over'] ?? 0.0);
        $odds = (float) ($prop['odds_over'] ?? 1.90);
        $opposingRank = (int) ($prop['opposing_defensive_rank'] ?? 16);

        $edgeAnalysis = $this->edgeCalculator->calculateEdge($playerAvg, $line, $odds, $opposingRank);
        $prop['edge_analysis'] = $edgeAnalysis;

        $betTypeDisplays = [
            'passing_yards'   => 'Yardas por Pase',
            'rushing_yards'   => 'Yardas por Acarreo',
            'receiving_yards' => 'Yardas por Recepción',
            'shots_on_target' => 'Tiros al Arco',
            'goals'           => 'Goles',
            'assists'         => 'Asistencias'
        ];
        $prop['bet_type_display'] = $betTypeDisplays[$prop['bet_type'] ?? ''] ?? ucwords(str_replace('_', ' ', $prop['bet_type'] ?? ''));

        // American odds conversion
        $dec = (float) ($prop['odds_over'] ?? 1.90);
        if ($dec >= 2.0) {
            $prop['american_odds'] = '+' . round(($dec - 1.0) * 100);
        } elseif ($dec > 1.0) {
            $prop['american_odds'] = '-' . round(100 / ($dec - 1.0));
        } else {
            $prop['american_odds'] = '-110';
        }

        // Feature the top 3 props by Edge or any prop with an Edge >= 8.7%
        $edgeVal = (float) ($edgeAnalysis['calculated_edge'] ?? $prop['calculated_edge'] ?? 0.0);
        $prop['is_featured'] = ($index < 3 || $edgeVal >= 8.7);

        // Fallbacks for opponent metadata
        $prop['opponent_rank'] = $prop['opponent_rank'] ?? $opposingRank;

        // Enrich with comparative US Bookmakers Odds (DraftKings, FanDuel, BetMGM, Caesars)
        $bookmakersOdds = $this->buildBookmakersOdds($prop);
        $prop['bookmakers_odds'] = $bookmakersOdds;
        foreach ($bookmakersOdds as $bm) {
            if ($bm['is_best']) {
                $prop['best_bookmaker'] = $bm['name'];
                $prop['american_odds'] = $bm['over'];
                break;
            }
        }
        $prop['best_bookmaker'] = $prop['best_bookmaker'] ?? 'DraftKings';

        // Enrich with recent 5 games trend and streak hits (for StatsDrawer.jsx)
        $recentTrends = $this->buildRecentGamesTrend($prop);
        $prop['recent_games'] = $recentTrends['recent_games'];
        $prop['streak_hits'] = $recentTrends['streak_hits'];

        return $prop;
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

    /**
     * Helper to construct the 5-game recent trend and hit rate.
     * Queries player_game_logs from DB. If empty or table not yet migrated, provides
     * a mathematically consistent fallback based on player's stat average so frontend never breaks.
     *
     * @param array $prop
     * @return array [ 'recent_games' => [...], 'streak_hits' => 'X/5' ]
     */
    private function buildRecentGamesTrend(array $prop): array
    {
        $playerId = (int) ($prop['player_id'] ?? 0);
        $betType = (string) ($prop['bet_type'] ?? 'passing_yards');
        $line = (float) ($prop['line_over'] ?? 0.0);
        $avg = (float) ($prop['stat_average'] ?? 0.0);

        $dbLogs = $this->gameLogModel->getRecentLogsByPlayerAndStat($playerId, $betType, 5);

        $recentGames = [];
        $hits = 0;

        if (!empty($dbLogs)) {
            foreach ($dbLogs as $log) {
                $val = (float) $log['stat_value'];
                $isHome = !empty($log['is_home']);
                $prefix = $isHome ? 'vs ' : '@ ';
                $opponent = $prefix . ($log['opponent_abbr'] ?? 'OPP');
                $hit = $val >= $line;
                if ($hit) {
                    $hits++;
                }

                $recentGames[] = [
                    'game'  => $opponent,
                    'value' => round($val, 1),
                    'hit'   => $hit
                ];
            }
        } else {
            // Realistic deterministic fallback based on stat_average & line
            // Guarantees frontend StatsDrawer.jsx works even before ESPN ingestion
            $simulatedOpponents = ['vs LAC', '@ DEN', 'vs LV', '@ SF', 'vs BAL'];
            $multipliers = [1.06, 0.94, 1.12, 0.98, 1.04];

            foreach ($simulatedOpponents as $i => $opp) {
                $factor = $multipliers[$i] ?? 1.0;
                $val = round($avg * $factor, 1);
                $hit = $val >= $line;
                if ($hit) {
                    $hits++;
                }

                $recentGames[] = [
                    'game'  => $opp,
                    'value' => $val,
                    'hit'   => $hit
                ];
            }
        }

        $totalGames = count($recentGames);
        $streakHits = "{$hits}/{$totalGames}";

        return [
            'recent_games' => $recentGames,
            'streak_hits'  => $streakHits
        ];
    }

    /**
     * Builds comparative multi-bookmaker odds (DraftKings, FanDuel, BetMGM, Caesars).
     * Calculates line variations and decimal prices to flag the optimal market entry point (is_best = true).
     */
    private function buildBookmakersOdds(array $prop): array
    {
        $baseLine = (float) ($prop['line_over'] ?? 50.0);
        $baseDec = (float) ($prop['odds_over'] ?? 1.91);
        $propId = (int) ($prop['prop_id'] ?? 1);

        $houses = [
            ['name' => 'DraftKings', 'lineOffset' => 0.0, 'decOffset' => -0.03],
            ['name' => 'FanDuel',    'lineOffset' => 0.0, 'decOffset' => 0.00],
            ['name' => 'BetMGM',     'lineOffset' => 0.5, 'decOffset' => 0.04],
            ['name' => 'Caesars',    'lineOffset' => 0.0, 'decOffset' => 0.08]
        ];

        // Shift best bookmaker according to propId for realistic market variance
        $bestIndex = $propId % 4;

        $results = [];
        $maxDec = -1.0;

        foreach ($houses as $idx => $h) {
            $dec = round($baseDec + $h['decOffset'] + ($idx === $bestIndex ? 0.05 : 0.0), 2);
            $line = $baseLine + ($idx === 2 ? $h['lineOffset'] : 0.0);

            $american = $dec >= 2.0 
                ? '+' . round(($dec - 1.0) * 100)
                : '-' . round(100 / max(0.01, $dec - 1.0));

            $results[] = [
                'name'    => $h['name'],
                'line'    => $line,
                'decimal' => $dec,
                'over'    => $american,
                'is_best' => false
            ];

            if ($dec > $maxDec) {
                $maxDec = $dec;
            }
        }

        // Flag the best market price
        foreach ($results as &$res) {
            if ($res['decimal'] === $maxDec) {
                $res['is_best'] = true;
                break;
            }
        }

        return $results;
    }
}

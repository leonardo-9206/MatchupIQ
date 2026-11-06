<?php
/**
 * EdgeCalculatorService
 * Statistical engine responsible for computing the expected positive value (+EV / Value Edge)
 * for betting player props and tactical coverage scheme modifiers.
 *
 * Mathematical Foundation:
 * 1. Implied Probability:
 *    - Decimal odds: P_implied = (1 / odds) * 100
 *    - American odds (> 0): 100 / (odds + 100) * 100
 *    - American odds (< 0): |odds| / (|odds| + 100) * 100
 *
 * 2. Opponent Defensive Matchup Factor:
 *    - Defensive rankings range from 1 (elite defense) to 32 (weakest defense).
 *    - Baseline: Rank 16.5 is neutral (factor 1.0).
 *    - Rank 32 adds an offensive boost up to +12%.
 *    - Rank 1 applies an offensive suppression up to -12%.
 *
 * 3. Projected / True Probability:
 *    - Projects performance comparing adjusted player baseline vs the line.
 *    - P_projected = 50% + [(adjusted_average - line) / line] * sensitivity
 *
 * 4. Value Edge (+EV) & Tactical Coverage Modifier (HU-11):
 *    - Edge = (P_projected - P_implied) + Tactical_Edge_Boost
 *    - A positive Edge indicates betting value where market misprices true likelihood.
 */

class EdgeCalculatorService
{
    private const BASE_PROBABILITY = 50.0;
    private const SENSITIVITY_FACTOR = 45.0; // Scaling factor for projection deltas
    private const MAX_DEFENSIVE_ADJUSTMENT = 0.12; // +/- 12% maximum modifier based on defense
    private const MAX_TACTICAL_BOOST = 6.0; // +/- 6.0 percentage points max tactical scheme modifier

    /**
     * Calculates the statistical Edge (+EV) for a given prop line and market odds.
     *
     * @param float $playerAverage Historical baseline stat average of the athlete
     * @param float $line Over/Under line threshold offered by the bookmaker
     * @param float $odds Market odds in decimal format (e.g. 1.90) or American format (e.g. -110, +130)
     * @param int $opposingDefensiveRank Defensive ranking of the opponent (1 to 32)
     * @param float $tacticalEdgeBoost Optional tactical coverage scheme modifier (in percentage points)
     * @return array Detailed analytical breakdown
     */
    public function calculateEdge(
        float $playerAverage,
        float $line,
        float $odds,
        int $opposingDefensiveRank = 16,
        float $tacticalEdgeBoost = 0.0
    ): array {
        // Defensive rank clamped between 1 (best) and 32 (worst)
        $rank = max(1, min(32, $opposingDefensiveRank));

        // Calculate implied probability from market odds
        $impliedProbability = $this->calculateImpliedProbability($odds);

        // Calculate matchup multiplier based on opponent's defensive ranking
        // Ranks 17..32 yield > 1.0 (weaker defense, favorable matchup)
        // Ranks 1..16 yield < 1.0 (strong defense, unfavorable matchup)
        $defensiveDelta = ($rank - 16.5) / 15.5; // Normalized to range [-1.0, 1.0]
        $matchupMultiplier = 1.0 + ($defensiveDelta * self::MAX_DEFENSIVE_ADJUSTMENT);

        // Adjusted projected baseline stat
        $adjustedStatAverage = $playerAverage * $matchupMultiplier;

        // Projected probability of exceeding the line (Over)
        $projectedProbability = $this->estimateProjectedProbability($adjustedStatAverage, $line);

        // Expected Value Edge (+EV) including tactical scheme boost when applicable
        $calculatedEdge = round(($projectedProbability - $impliedProbability) + $tacticalEdgeBoost, 2);

        // Grade recommendation
        $recommendation = $this->determineRecommendation($calculatedEdge);

        return [
            'player_average'        => round($playerAverage, 2),
            'line'                  => round($line, 2),
            'odds'                  => round($odds, 2),
            'defensive_rank'        => $rank,
            'matchup_multiplier'    => round($matchupMultiplier, 4),
            'adjusted_projection'   => round($adjustedStatAverage, 2),
            'implied_probability'   => round($impliedProbability, 2),
            'projected_probability' => round($projectedProbability, 2),
            'tactical_edge_boost'   => round($tacticalEdgeBoost, 2),
            'calculated_edge'       => $calculatedEdge,
            'recommendation'        => $recommendation
        ];
    }

    /**
     * Computes the tactical Edge boost (in percentage points) for a receiver based on
     * Man vs Zone coverage alignment against the opposing defense's scheme tendencies.
     *
     * @param float $projectedCatchRate Weighted catch rate given opponent's Man/Zone frequency
     * @param float $overallCatchRate Baseline overall catch rate across all targets
     * @param float $schemeDifferential Absolute catch rate difference between preferred and secondary scheme
     * @param string $tacticalAdvantage 'FAVORABLE', 'UNFAVORABLE', or 'NEUTRAL'
     * @return float Tactical edge modifier in percentage points
     */
    public function calculateTacticalEdgeBoost(
        float $projectedCatchRate,
        float $overallCatchRate,
        float $schemeDifferential,
        string $tacticalAdvantage
    ): float {
        $catchRateDelta = ($projectedCatchRate - $overallCatchRate) * 1.5;

        $schemeBonus = 0.0;
        if ($tacticalAdvantage === 'FAVORABLE') {
            $schemeBonus = $schemeDifferential * 0.50;
        } elseif ($tacticalAdvantage === 'UNFAVORABLE') {
            $schemeBonus = -($schemeDifferential * 0.50);
        }

        $rawBoost = $catchRateDelta + $schemeBonus;
        $clampedBoost = max(-self::MAX_TACTICAL_BOOST, min(self::MAX_TACTICAL_BOOST, $rawBoost));

        return round($clampedBoost, 2);
    }

    /**
     * Converts market odds to percentage implied probability (0% - 100%).
     */
    public function calculateImpliedProbability(float $odds): float
    {
        if ($odds <= 0.0) {
            return 50.0;
        }

        // Decimal odds (e.g. 1.91, 2.50)
        if ($odds >= 1.01 && $odds <= 30.0) {
            return (1.0 / $odds) * 100.0;
        }

        // American positive odds (e.g. +150)
        if ($odds > 30.0) {
            return (100.0 / ($odds + 100.0)) * 100.0;
        }

        // American negative odds (e.g. -115)
        $absOdds = abs($odds);
        return ($absOdds / ($absOdds + 100.0)) * 100.0;
    }

    /**
     * Computes the modeled likelihood of the player hitting over the line.
     */
    private function estimateProjectedProbability(float $projectedAverage, float $line): float
    {
        if ($line <= 0.0) {
            return 50.0;
        }

        $percentageDiff = ($projectedAverage - $line) / $line;
        $projected = self::BASE_PROBABILITY + ($percentageDiff * self::SENSITIVITY_FACTOR);

        // Clamp between realistic bounds (5% to 95%)
        return max(5.0, min(95.0, $projected));
    }

    /**
     * Categorizes the calculated edge into actionable betting intelligence tiers.
     */
    private function determineRecommendation(float $edge): string
    {
        if ($edge >= 12.0) {
            return 'HIGH_VALUE';     // Strong +EV play
        }
        if ($edge >= 5.0) {
            return 'MODERATE_VALUE'; // Solid edge
        }
        if ($edge > 0.0) {
            return 'SLIGHT_VALUE';   // Marginal edge
        }
        return 'NO_VALUE';           // Unfavorable market line
    }
}

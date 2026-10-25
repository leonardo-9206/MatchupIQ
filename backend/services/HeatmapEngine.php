<?php
/**
 * HeatmapEngine Service
 * Processes NFL tactical field zones (Left End, Left Tackle, Middle, Right Tackle, Right End)
 * and defensive coverage schemes (Man vs Zone) into standardized analytical payloads.
 */

class HeatmapEngine
{
    public const FIELD_ZONES = [
        'left_end',
        'left_tackle',
        'middle',
        'right_tackle',
        'right_end',
    ];

    public const COVERAGE_SCHEMES = [
        'man',
        'zone',
    ];

    /**
     * Builds the standardized tactical heatmap breakdown and summary metrics for a ball-carrier.
     *
     * @param array $player Player profile record
     * @param array $zones Raw zone rows from player_heatmaps
     * @return array Standardized heatmap breakdown payload
     */
    public function buildHeatmapBreakdown(array $player, array $zones): array
    {
        $totalAttempts = 0;
        $totalYards = 0;
        $weightedSuccessSum = 0.0;
        $dominantZone = null;
        $maxAttempts = -1;

        foreach ($zones as $zone) {
            $attempts = (int) $zone['attempts'];
            $yards = (int) $zone['yards_gained'];
            $successRate = (float) $zone['success_rate'];

            $totalAttempts += $attempts;
            $totalYards += $yards;
            $weightedSuccessSum += ($attempts * $successRate);

            if ($attempts > $maxAttempts) {
                $maxAttempts = $attempts;
                $dominantZone = (string) $zone['field_zone'];
            }
        }

        $processedZones = [];
        foreach ($zones as $zone) {
            $attempts = (int) $zone['attempts'];
            $yards = (int) $zone['yards_gained'];
            $successRate = round((float) $zone['success_rate'], 2);

            $yardsPerAttempt = $attempts > 0 ? round($yards / $attempts, 2) : 0.0;
            $attemptShare = $totalAttempts > 0 ? round(($attempts / $totalAttempts) * 100, 2) : 0.0;

            $processedZones[] = [
                'heatmap_id'        => (int) $zone['heatmap_id'],
                'player_id'         => (int) $zone['player_id'],
                'field_zone'        => (string) $zone['field_zone'],
                'attempts'          => $attempts,
                'yards_gained'      => $yards,
                'success_rate'      => $successRate,
                'yards_per_attempt' => $yardsPerAttempt,
                'attempt_share'     => $attemptShare,
            ];
        }

        $overallYardsPerAttempt = $totalAttempts > 0 ? round($totalYards / $totalAttempts, 2) : 0.0;
        $overallSuccessRate = $totalAttempts > 0 ? round($weightedSuccessSum / $totalAttempts, 2) : 0.0;

        return [
            'player'  => $player,
            'zones'   => $processedZones,
            'summary' => [
                'total_attempts'       => $totalAttempts,
                'total_yards'          => $totalYards,
                'yards_per_attempt'    => $overallYardsPerAttempt,
                'overall_success_rate' => $overallSuccessRate,
                'dominant_zone'        => $dominantZone,
            ],
        ];
    }

    /**
     * Builds the standardized Man vs Zone coverage breakdown for a receiver or tight end.
     *
     * @param array $player Player profile record
     * @param array $splits Raw coverage split rows from coverage_splits
     * @return array Standardized coverage breakdown payload
     */
    public function buildCoverageBreakdown(array $player, array $splits): array
    {
        $totalTargets = 0;
        $totalReceptions = 0;
        $preferredScheme = null;
        $bestCatchRate = -1.0;

        foreach ($splits as $split) {
            $targets = (int) $split['targets'];
            $receptions = (int) $split['receptions'];
            $catchRate = (float) $split['catch_rate'];

            $totalTargets += $targets;
            $totalReceptions += $receptions;

            if ($catchRate > $bestCatchRate) {
                $bestCatchRate = $catchRate;
                $preferredScheme = (string) $split['coverage_type'];
            }
        }

        $processedSplits = [];
        foreach ($splits as $split) {
            $targets = (int) $split['targets'];
            $receptions = (int) $split['receptions'];
            $catchRate = round((float) $split['catch_rate'], 2);
            $targetShare = $totalTargets > 0 ? round(($targets / $totalTargets) * 100, 2) : 0.0;

            $processedSplits[] = [
                'split_id'      => (int) $split['split_id'],
                'player_id'     => (int) $split['player_id'],
                'coverage_type' => (string) $split['coverage_type'],
                'targets'       => $targets,
                'receptions'    => $receptions,
                'catch_rate'    => $catchRate,
                'target_share'  => $targetShare,
            ];
        }

        $overallCatchRate = $totalTargets > 0 ? round(($totalReceptions / $totalTargets) * 100, 2) : 0.0;

        return [
            'player'  => $player,
            'splits'  => $processedSplits,
            'summary' => [
                'total_targets'      => $totalTargets,
                'total_receptions'   => $totalReceptions,
                'overall_catch_rate' => $overallCatchRate,
                'preferred_scheme'   => $preferredScheme,
            ],
        ];
    }
}

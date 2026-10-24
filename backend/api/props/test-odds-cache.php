<?php
/**
 * Test endpoint for OddsApiService & Cache Proxy
 * Usage: GET /api/props/test-odds-cache.php?sport=americanfootball_nfl&refresh=0
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../services/OddsApiService.php';

$startTime = microtime(true);

$sport = isset($_GET['sport']) && is_string($_GET['sport']) ? trim($_GET['sport']) : 'americanfootball_nfl';
$forceRefresh = isset($_GET['refresh']) && ($_GET['refresh'] === '1' || $_GET['refresh'] === 'true');

try {
    $service = new OddsApiService();
    $result = $service->getOdds($sport, ['h2h'], $forceRefresh);

    $executionTimeMs = round((microtime(true) - $startTime) * 1000, 2);

    echo json_encode([
        'status'            => 'success',
        'benchmark_ms'      => $executionTimeMs,
        'within_sla'        => $executionTimeMs < 250, // Confirms RNF-05 (< 250ms SLA)
        'payload'           => $result
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

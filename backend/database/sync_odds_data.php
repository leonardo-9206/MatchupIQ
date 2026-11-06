<?php
/**
 * CLI Script: Synchronize Live Odds from The Odds API
 * Usage: php backend/database/sync_odds_data.php
 * Description: Connects to The Odds API via OddsApiService, caches raw lines,
 * and updates relational odds in the `props` database table.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/OddsApiService.php';

echo "====================================================\n";
echo " MatchupIQ — The Odds API Live Data Ingestion Runner\n";
echo "====================================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $apiKey = getenv('ODDS_API_KEY');
    if (empty($apiKey) || $apiKey === 'your_the_odds_api_key_here') {
        echo "[!] ERROR: ODDS_API_KEY is not configured in backend/.env\n";
        exit(1);
    }

    echo "[*] Using API Key: " . substr($apiKey, 0, 6) . "..." . substr($apiKey, -4) . " (Length: " . strlen($apiKey) . " chars)\n";
    echo "[*] Connecting to The Odds API (NFL / h2h, spreads, totals)...\n";

    $oddsService = new OddsApiService();
    $result = $oddsService->getOdds('americanfootball_nfl', ['h2h'], true);

    if (!$result['success']) {
        echo "\n[!] API Sync Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        echo "[i] Source: " . ($result['source'] ?? 'unknown') . "\n";
        exit(1);
    }

    echo "\n[+] Synchronization Finished Successfully!\n";
    echo "  - Source:               " . ($result['source'] ?? 'external_api') . "\n";
    echo "  - Events Retrieved:     " . ($result['count'] ?? 0) . "\n";
    echo "  - Requests Remaining:   " . ($result['requests_remaining'] ?? 'N/A') . "\n";
    if (isset($result['props_synced'])) {
        echo "  - Props Updated:        " . ($result['props_synced']['updated'] ?? 0) . "\n";
        echo "  - Props Inserted:       " . ($result['props_synced']['inserted'] ?? 0) . "\n";
        echo "  - Events Processed:     " . ($result['props_synced']['events_processed'] ?? 0) . "\n";
    }

    echo "\n[+] Done! Odds table and props cache successfully updated.\n";
    exit(0);

} catch (Throwable $e) {
    echo "\n[!] Ingestion Error: " . $e->getMessage() . "\n";
    exit(1);
}

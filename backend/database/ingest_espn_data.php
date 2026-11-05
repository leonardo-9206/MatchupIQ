<?php
/**
 * CLI Script: Ingest Real Historical Game Logs from ESPN API
 * Usage: php backend/database/ingest_espn_data.php
 * Description: Populates `player_game_logs` and expands athlete catalog with real NFL data.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/EspnApiService.php';

echo "====================================================\n";
echo " MatchupIQ — ESPN API Live Data Ingestion Runner\n";
echo "====================================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $espnService = new EspnApiService();

    echo "[*] Connecting to ESPN Public API and synchronizing athlete trends...\n";
    $result = $espnService->syncAllPresetAthletes(5);

    echo "\n[+] Synchronization Finished Successfully!\n";
    echo "  - Athletes Processed:   " . $result['athletes_processed'] . "\n";
    echo "  - Athletes Synced:      " . $result['athletes_synced'] . "\n";
    echo "  - Players Created:      " . $result['players_created'] . "\n";
    echo "  - New Props Created:    " . $result['props_created'] . "\n";
    echo "  - Total Logs Ingested:  " . $result['total_logs_ingested'] . "\n\n";

    echo "--- Ingestion Details ---\n";
    foreach ($result['details'] as $detail) {
        echo sprintf(
            "  [%s] %-22s (%s - %s) -> %d games ingested\n",
            $detail['source'],
            $detail['player'],
            $detail['position'],
            $detail['team'],
            $detail['logs_count']
        );
    }

    echo "\n[+] Done! player_game_logs is populated with real sports performance data.\n";
    exit(0);

} catch (Throwable $e) {
    echo "\n[!] Ingestion Error: " . $e->getMessage() . "\n";
    exit(1);
}

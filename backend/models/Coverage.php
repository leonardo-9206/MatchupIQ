<?php
/**
 * Coverage Model
 * Queries offensive player performance splits against Man vs Zone defensive coverage.
 * Adheres to 3NF relational schema and utilizes PDO prepared statements.
 */

require_once __DIR__ . '/../config/Database.php';

class Coverage
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves Man and Zone coverage splits for a specific player ID.
     *
     * @param int $playerId Unique player identifier
     * @return array List of coverage split records
     */
    public function getSplitsByPlayerId(int $playerId): array
    {
        $sql = "SELECT 
                    split_id,
                    player_id,
                    coverage_type,
                    targets,
                    receptions,
                    CAST(catch_rate AS FLOAT) AS catch_rate
                FROM coverage_splits
                WHERE player_id = :player_id
                ORDER BY FIELD(coverage_type, 'man', 'zone')";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':player_id', $playerId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();

        return array_map(static function (array $row): array {
            return [
                'split_id'      => (int) $row['split_id'],
                'player_id'     => (int) $row['player_id'],
                'coverage_type' => (string) $row['coverage_type'],
                'targets'       => (int) $row['targets'],
                'receptions'    => (int) $row['receptions'],
                'catch_rate'    => round((float) $row['catch_rate'], 2),
            ];
        }, $rows);
    }
}

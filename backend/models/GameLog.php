<?php
/**
 * GameLog Model (DataSource)
 * Encapsulates all database operations for historical player game logs (`player_game_logs`).
 * Section 2.12 in DATA_DICTIONARY.md.
 * Used to compute 5-game recent trends and streak hit-rates ('X/5 Over') for player props.
 */

require_once __DIR__ . '/../config/Database.php';

class GameLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves the most recent game logs for a given player and stat type.
     *
     * @param int $playerId Evaluated athlete ID
     * @param string $statType Stat market (e.g. 'passing_yards', 'rushing_yards')
     * @param int $limit Number of recent games (default 5)
     * @return array List of recent match logs
     */
    public function getRecentLogsByPlayerAndStat(int $playerId, string $statType, int $limit = 5): array
    {
        // First verify if player_game_logs table exists (graceful degradation if Rodrigo's migration is pending)
        if (!$this->tableExists('player_game_logs')) {
            return [];
        }

        $sql = "SELECT 
                    log_id,
                    player_id,
                    opponent_abbr,
                    stat_type,
                    CAST(stat_value AS FLOAT) AS stat_value,
                    game_date,
                    is_home
                FROM player_game_logs
                WHERE player_id = :player_id AND stat_type = :stat_type
                ORDER BY game_date DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':player_id', $playerId, PDO::PARAM_INT);
        $stmt->bindValue(':stat_type', $statType, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Checks if a table exists in the database.
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE :table_name");
            $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

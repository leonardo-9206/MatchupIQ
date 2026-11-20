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
    private static array $tableExistsCache = [];

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
        // First verify if player_game_logs table exists (graceful degradation if migration is pending)
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
     * Retrieves recent game logs for multiple players in a single batch query.
     * Prevents N+1 roundtrips over high-latency database connections.
     *
     * @param int[] $playerIds Array of player IDs to fetch logs for
     * @param int $limitPerPlayer Maximum recent logs per player and stat type (default 5)
     * @return array Multi-dimensional map: [player_id => [stat_type => [logs...]]]
     */
    public function getRecentLogsBatch(array $playerIds, int $limitPerPlayer = 5): array
    {
        $playerIds = array_values(array_unique(array_filter(array_map('intval', $playerIds), fn($id) => $id > 0)));
        if (empty($playerIds) || !$this->tableExists('player_game_logs')) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($playerIds), '?'));

        try {
            $sql = "
                WITH ranked_logs AS (
                    SELECT 
                        log_id,
                        player_id,
                        opponent_abbr,
                        stat_type,
                        CAST(stat_value AS FLOAT) AS stat_value,
                        game_date,
                        is_home,
                        ROW_NUMBER() OVER (PARTITION BY player_id, stat_type ORDER BY game_date DESC) AS rn
                    FROM player_game_logs
                    WHERE player_id IN ($placeholders)
                )
                SELECT log_id, player_id, opponent_abbr, stat_type, stat_value, game_date, is_home
                FROM ranked_logs
                WHERE rn <= ?
                ORDER BY player_id, stat_type, game_date DESC
            ";

            $stmt = $this->db->prepare($sql);
            $params = array_merge($playerIds, [$limitPerPlayer]);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            // Fallback for SQL engines without CTE / window function support
            $sql = "
                SELECT 
                    log_id,
                    player_id,
                    opponent_abbr,
                    stat_type,
                    CAST(stat_value AS FLOAT) AS stat_value,
                    game_date,
                    is_home
                FROM player_game_logs
                WHERE player_id IN ($placeholders)
                ORDER BY player_id, stat_type, game_date DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($playerIds);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $grouped = [];
        foreach ($rows as $row) {
            $pId = (int) $row['player_id'];
            $sType = $row['stat_type'];
            if (!isset($grouped[$pId])) {
                $grouped[$pId] = [];
            }
            if (!isset($grouped[$pId][$sType])) {
                $grouped[$pId][$sType] = [];
            }
            if (count($grouped[$pId][$sType]) < $limitPerPlayer) {
                $grouped[$pId][$sType][] = $row;
            }
        }

        return $grouped;
    }

    /**
     * Checks if a table exists in the database with static in-memory memoization.
     */
    public function tableExists(string $tableName): bool
    {
        if (isset(self::$tableExistsCache[$tableName])) {
            return self::$tableExistsCache[$tableName];
        }

        try {
            $stmt = $this->db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name LIMIT 1");
            $stmt->bindValue(':table_name', $tableName, PDO::PARAM_STR);
            $stmt->execute();
            return self::$tableExistsCache[$tableName] = (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return self::$tableExistsCache[$tableName] = false;
        }
    }
}

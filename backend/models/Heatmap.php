<?php
/**
 * Heatmap Model
 * Queries NFL ball-carrier rushing and passing zone metrics from MySQL.
 * Adheres to 3NF relational schema and utilizes PDO prepared statements.
 */

require_once __DIR__ . '/../config/Database.php';

class Heatmap
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves player profile along with team and sport metadata by player ID.
     *
     * @param int $playerId Unique player identifier
     * @return array|null Player details or null if not found
     */
    public function getPlayerById(int $playerId): ?array
    {
        $sql = "SELECT 
                    pl.player_id,
                    pl.name,
                    pl.position,
                    CAST(pl.stat_average AS FLOAT) AS stat_average,
                    t.team_id,
                    t.name AS team_name,
                    t.abbreviation AS team_abbreviation,
                    t.defensive_rank,
                    s.sport_id,
                    s.name AS sport_name,
                    s.slug AS sport_slug
                FROM players pl
                INNER JOIN teams t ON pl.team_id = t.team_id
                INNER JOIN sports s ON t.sport_id = s.sport_id
                WHERE pl.player_id = :player_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':player_id', $playerId, PDO::PARAM_INT);
        $stmt->execute();

        $player = $stmt->fetch();
        if (!$player) {
            return null;
        }

        $player['player_id'] = (int) $player['player_id'];
        $player['stat_average'] = (float) $player['stat_average'];
        $player['team_id'] = (int) $player['team_id'];
        $player['defensive_rank'] = (int) $player['defensive_rank'];
        $player['sport_id'] = (int) $player['sport_id'];

        return $player;
    }

    /**
     * Retrieves all offensive field zone records for a specific player ordered left to right.
     *
     * @param int $playerId Unique player identifier
     * @return array Ordered list of field zone metrics
     */
    public function getZonesByPlayerId(int $playerId): array
    {
        $sql = "SELECT 
                    heatmap_id,
                    player_id,
                    field_zone,
                    attempts,
                    yards_gained,
                    CAST(success_rate AS FLOAT) AS success_rate
                FROM player_heatmaps
                WHERE player_id = :player_id
                ORDER BY FIELD(field_zone, 'left_end', 'left_tackle', 'middle', 'right_tackle', 'right_end')";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':player_id', $playerId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();

        return array_map(static function (array $row): array {
            return [
                'heatmap_id'   => (int) $row['heatmap_id'],
                'player_id'    => (int) $row['player_id'],
                'field_zone'   => (string) $row['field_zone'],
                'attempts'     => (int) $row['attempts'],
                'yards_gained' => (int) $row['yards_gained'],
                'success_rate' => round((float) $row['success_rate'], 2),
            ];
        }, $rows);
    }
}

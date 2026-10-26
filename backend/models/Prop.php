<?php
/**
 * Prop Model
 * Queries betting props, odds, calculated edges, and related player/team data.
 * Adheres to 3NF relational schema and utilizes prepared statements.
 */

require_once __DIR__ . '/../config/Database.php';

class Prop
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves all available player props with related game, team, and bookmaker details.
     *
     * @param string|null $sportSlug Optional filter by sport (e.g. 'nfl', 'ligamx')
     * @param int $limit Maximum records to return (default 50)
     * @return array List of props
     */
    public function getAll(?string $sportSlug = null, int $limit = 50): array
    {
        $sql = "SELECT 
                    p.prop_id,
                    p.bet_type,
                    CAST(p.line_over AS FLOAT) AS line_over,
                    CAST(p.odds_over AS FLOAT) AS odds_over,
                    CAST(p.odds_under AS FLOAT) AS odds_under,
                    CAST(p.calculated_edge AS FLOAT) AS calculated_edge,
                    p.updated_at,
                    pl.player_id,
                    pl.name AS player_name,
                    pl.position AS player_position,
                    CAST(pl.stat_average AS FLOAT) AS stat_average,
                    t.team_id,
                    t.name AS team_name,
                    t.abbreviation AS team_abbreviation,
                    t.defensive_rank,
                    b.bookmaker_id,
                    b.name AS bookmaker_name,
                    g.game_id,
                    g.game_date,
                    s.name AS sport_name,
                    s.slug AS sport_slug
                FROM props p
                INNER JOIN players pl ON p.player_id = pl.player_id
                INNER JOIN teams t ON pl.team_id = t.team_id
                INNER JOIN sports s ON t.sport_id = s.sport_id
                INNER JOIN bookmakers b ON p.bookmaker_id = b.bookmaker_id
                INNER JOIN games g ON p.game_id = g.game_id";

        if ($sportSlug !== null && !empty($sportSlug)) {
            $sql .= " WHERE s.slug = :sport_slug";
        }

        $sql .= " ORDER BY p.calculated_edge DESC, p.updated_at DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);

        if ($sportSlug !== null && !empty($sportSlug)) {
            $stmt->bindValue(':sport_slug', $sportSlug, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Find a specific prop by ID with full details.
     */
    public function findById(int $propId): ?array
    {
        $sql = "SELECT 
                    p.prop_id,
                    p.bet_type,
                    CAST(p.line_over AS FLOAT) AS line_over,
                    CAST(p.odds_over AS FLOAT) AS odds_over,
                    CAST(p.odds_under AS FLOAT) AS odds_under,
                    CAST(p.calculated_edge AS FLOAT) AS calculated_edge,
                    p.updated_at,
                    pl.player_id,
                    pl.name AS player_name,
                    pl.position AS player_position,
                    CAST(pl.stat_average AS FLOAT) AS stat_average,
                    t.name AS team_name,
                    t.abbreviation AS team_abbreviation,
                    b.name AS bookmaker_name,
                    g.game_date
                FROM props p
                INNER JOIN players pl ON p.player_id = pl.player_id
                INNER JOIN teams t ON pl.team_id = t.team_id
                INNER JOIN bookmakers b ON p.bookmaker_id = b.bookmaker_id
                INNER JOIN games g ON p.game_id = g.game_id
                WHERE p.prop_id = :prop_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':prop_id', $propId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result ?: null;
    }
}

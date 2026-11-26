<?php
/**
 * Player Model
 * Handles CRUD operations for the `players` table.
 */

require_once __DIR__ . '/../config/Database.php';

class Player
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT
                    p.player_id,
                    p.team_id,
                    t.name AS team_name,
                    t.abbreviation AS team_abbreviation,
                    s.name AS sport_name,
                    p.name,
                    p.position,
                    CAST(p.stat_average AS FLOAT) AS stat_average
                FROM players p
                INNER JOIN teams t ON p.team_id = t.team_id
                INNER JOIN sports s ON t.sport_id = s.sport_id
                ORDER BY p.player_id DESC";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'formatRow'], $rows);
    }

    public function findById(int $playerId): ?array
    {
        $sql = "SELECT
                    p.player_id,
                    p.team_id,
                    t.name AS team_name,
                    t.abbreviation AS team_abbreviation,
                    s.name AS sport_name,
                    p.name,
                    p.position,
                    CAST(p.stat_average AS FLOAT) AS stat_average
                FROM players p
                INNER JOIN teams t ON p.team_id = t.team_id
                INNER JOIN sports s ON t.sport_id = s.sport_id
                WHERE p.player_id = :player_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':player_id', $playerId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->formatRow($row) : null;
    }

    public function teamExists(int $teamId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM teams WHERE team_id = :team_id LIMIT 1");
        $stmt->bindValue(':team_id', $teamId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function create(int $teamId, string $name, string $position, float $statAverage): array
    {
        $sql = "INSERT INTO players (team_id, name, position, stat_average)
                VALUES (:team_id, :name, :position, :stat_average)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':team_id', $teamId, PDO::PARAM_INT);
        $stmt->bindValue(':name', trim($name), PDO::PARAM_STR);
        $stmt->bindValue(':position', strtoupper(trim($position)), PDO::PARAM_STR);
        $stmt->bindValue(':stat_average', number_format($statAverage, 2, '.', ''), PDO::PARAM_STR);
        $stmt->execute();

        $newId = (int) $this->db->lastInsertId();
        return $this->findById($newId) ?? [
            'player_id'    => $newId,
            'team_id'      => $teamId,
            'name'         => trim($name),
            'position'     => strtoupper(trim($position)),
            'stat_average' => round($statAverage, 2)
        ];
    }

    public function update(int $playerId, array $fields): ?array
    {
        $existing = $this->findById($playerId);
        if (!$existing) {
            return null;
        }

        $sets = [];
        $params = [':player_id' => $playerId];

        if (isset($fields['team_id'])) {
            $sets[] = 'team_id = :team_id';
            $params[':team_id'] = (int) $fields['team_id'];
        }
        if (isset($fields['name'])) {
            $sets[] = 'name = :name';
            $params[':name'] = trim((string) $fields['name']);
        }
        if (isset($fields['position'])) {
            $sets[] = 'position = :position';
            $params[':position'] = strtoupper(trim((string) $fields['position']));
        }
        if (isset($fields['stat_average'])) {
            $sets[] = 'stat_average = :stat_average';
            $params[':stat_average'] = number_format((float) $fields['stat_average'], 2, '.', '');
        }

        if (!empty($sets)) {
            $sql = 'UPDATE players SET ' . implode(', ', $sets) . ' WHERE player_id = :player_id';
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
        }

        return $this->findById($playerId);
    }

    public function delete(int $playerId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM players WHERE player_id = :player_id");
        $stmt->bindValue(':player_id', $playerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function formatRow(array $row): array
    {
        return [
            'player_id'         => (int) $row['player_id'],
            'team_id'           => (int) $row['team_id'],
            'team_name'         => $row['team_name'],
            'team_abbreviation' => $row['team_abbreviation'],
            'sport_name'        => $row['sport_name'],
            'name'              => $row['name'],
            'position'          => $row['position'],
            'stat_average'      => round((float) $row['stat_average'], 2),
        ];
    }
}

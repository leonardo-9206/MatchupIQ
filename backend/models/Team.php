<?php
/**
 * Team Model
 * Handles CRUD operations for the `teams` table.
 */

require_once __DIR__ . '/../config/Database.php';

class Team
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array
    {
        $sql = "SELECT
                    t.team_id,
                    t.sport_id,
                    s.name AS sport_name,
                    t.name,
                    t.abbreviation,
                    t.defensive_rank
                FROM teams t
                INNER JOIN sports s ON t.sport_id = s.sport_id
                ORDER BY t.sport_id ASC, t.name ASC";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'formatRow'], $rows);
    }

    public function findById(int $teamId): ?array
    {
        $sql = "SELECT
                    t.team_id,
                    t.sport_id,
                    s.name AS sport_name,
                    t.name,
                    t.abbreviation,
                    t.defensive_rank
                FROM teams t
                INNER JOIN sports s ON t.sport_id = s.sport_id
                WHERE t.team_id = :team_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':team_id', $teamId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->formatRow($row) : null;
    }

    public function sportExists(int $sportId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM sports WHERE sport_id = :sport_id LIMIT 1");
        $stmt->bindValue(':sport_id', $sportId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function create(int $sportId, string $name, string $abbreviation, int $defensiveRank): array
    {
        $sql = "INSERT INTO teams (sport_id, name, abbreviation, defensive_rank)
                VALUES (:sport_id, :name, :abbreviation, :defensive_rank)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':sport_id', $sportId, PDO::PARAM_INT);
        $stmt->bindValue(':name', trim($name), PDO::PARAM_STR);
        $stmt->bindValue(':abbreviation', strtoupper(trim($abbreviation)), PDO::PARAM_STR);
        $stmt->bindValue(':defensive_rank', $defensiveRank, PDO::PARAM_INT);
        $stmt->execute();

        $newId = (int) $this->db->lastInsertId();
        return $this->findById($newId) ?? [
            'team_id'        => $newId,
            'sport_id'       => $sportId,
            'name'           => trim($name),
            'abbreviation'   => strtoupper(trim($abbreviation)),
            'defensive_rank' => $defensiveRank
        ];
    }

    public function update(int $teamId, array $fields): ?array
    {
        $existing = $this->findById($teamId);
        if (!$existing) {
            return null;
        }

        $sets = [];
        $params = [':team_id' => $teamId];

        if (isset($fields['sport_id'])) {
            $sets[] = 'sport_id = :sport_id';
            $params[':sport_id'] = (int) $fields['sport_id'];
        }
        if (isset($fields['name'])) {
            $sets[] = 'name = :name';
            $params[':name'] = trim((string) $fields['name']);
        }
        if (isset($fields['abbreviation'])) {
            $sets[] = 'abbreviation = :abbreviation';
            $params[':abbreviation'] = strtoupper(trim((string) $fields['abbreviation']));
        }
        if (isset($fields['defensive_rank'])) {
            $sets[] = 'defensive_rank = :defensive_rank';
            $params[':defensive_rank'] = (int) $fields['defensive_rank'];
        }

        if (!empty($sets)) {
            $sql = 'UPDATE teams SET ' . implode(', ', $sets) . ' WHERE team_id = :team_id';
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
        }

        return $this->findById($teamId);
    }

    public function delete(int $teamId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM teams WHERE team_id = :team_id");
        $stmt->bindValue(':team_id', $teamId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function formatRow(array $row): array
    {
        return [
            'team_id'        => (int) $row['team_id'],
            'sport_id'       => (int) $row['sport_id'],
            'sport_name'     => $row['sport_name'],
            'name'           => $row['name'],
            'abbreviation'   => $row['abbreviation'],
            'defensive_rank' => (int) $row['defensive_rank'],
        ];
    }
}

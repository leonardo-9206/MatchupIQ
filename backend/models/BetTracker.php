<?php
/**
 * BetTracker Model (DataSource)
 * Encapsulates all database interactions for the bet_tracker table.
 * Implements strict BOLA (Broken Object Level Authorization) controls by requiring user_id on state modifications.
 * Adheres to 3NF relational schema and utilizes prepared statements.
 */

require_once __DIR__ . '/../config/Database.php';

class BetTracker
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves all bets belonging to a specific authenticated user, enriched with prop, player, team, and game details.
     *
     * @param int $userId Authenticated user ID
     * @param string|null $statusFilter Optional filter: 'pending', 'won', 'lost', 'void'
     * @return array List of user bets
     */
    public function getByUserId(int $userId, ?string $statusFilter = null): array
    {
        $sql = "SELECT 
                    b.bet_id,
                    b.user_id,
                    b.prop_id,
                    CAST(b.taken_odds AS FLOAT) AS taken_odds,
                    CAST(b.stake_amount AS FLOAT) AS stake_amount,
                    b.status,
                    b.notes,
                    b.created_at,
                    p.bet_type,
                    CAST(p.line_over AS FLOAT) AS line_over,
                    CAST(p.odds_over AS FLOAT) AS odds_over,
                    CAST(p.calculated_edge AS FLOAT) AS calculated_edge,
                    pl.player_id,
                    pl.name AS player_name,
                    pl.position AS player_position,
                    t.name AS team_name,
                    t.abbreviation AS team_abbreviation,
                    bm.name AS bookmaker_name,
                    g.game_date
                FROM bet_tracker b
                INNER JOIN props p ON b.prop_id = p.prop_id
                INNER JOIN players pl ON p.player_id = pl.player_id
                INNER JOIN teams t ON pl.team_id = t.team_id
                INNER JOIN bookmakers bm ON p.bookmaker_id = bm.bookmaker_id
                INNER JOIN games g ON p.game_id = g.game_id
                WHERE b.user_id = :user_id";

        if ($statusFilter !== null && in_array($statusFilter, ['pending', 'won', 'lost', 'void'], true)) {
            $sql .= " AND b.status = :status";
        }

        $sql .= " ORDER BY b.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        if ($statusFilter !== null && in_array($statusFilter, ['pending', 'won', 'lost', 'void'], true)) {
            $stmt->bindValue(':status', $statusFilter, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Find a single bet by ID and ensure ownership by user (BOLA protection).
     */
    public function findByIdAndUser(int $betId, int $userId): ?array
    {
        $sql = "SELECT 
                    b.bet_id,
                    b.user_id,
                    b.prop_id,
                    CAST(b.taken_odds AS FLOAT) AS taken_odds,
                    CAST(b.stake_amount AS FLOAT) AS stake_amount,
                    b.status,
                    b.notes,
                    b.created_at,
                    p.bet_type,
                    CAST(p.line_over AS FLOAT) AS line_over,
                    pl.name AS player_name,
                    t.name AS team_name,
                    bm.name AS bookmaker_name
                FROM bet_tracker b
                INNER JOIN props p ON b.prop_id = p.prop_id
                INNER JOIN players pl ON p.player_id = pl.player_id
                INNER JOIN teams t ON pl.team_id = t.team_id
                INNER JOIN bookmakers bm ON p.bookmaker_id = bm.bookmaker_id
                WHERE b.bet_id = :bet_id AND b.user_id = :user_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':bet_id', $betId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Creates a new bet log entry.
     */
    public function create(int $userId, int $propId, float $takenOdds, float $stakeAmount, ?string $notes = null): int
    {
        $sql = "INSERT INTO bet_tracker (user_id, prop_id, taken_odds, stake_amount, status, notes, created_at)
                VALUES (:user_id, :prop_id, :taken_odds, :stake_amount, 'pending', :notes, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':prop_id', $propId, PDO::PARAM_INT);
        $stmt->bindValue(':taken_odds', $takenOdds);
        $stmt->bindValue(':stake_amount', $stakeAmount);
        $stmt->bindValue(':notes', $notes, $notes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates an existing bet's status or notes, strictly scoped to user_id (BOLA protection).
     */
    public function update(int $betId, int $userId, string $status, ?string $notes = null): bool
    {
        $sql = "UPDATE bet_tracker 
                SET status = :status, notes = :notes 
                WHERE bet_id = :bet_id AND user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $notes, $notes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':bet_id', $betId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Deletes a bet entry, strictly scoped to user_id (BOLA protection).
     */
    public function delete(int $betId, int $userId): bool
    {
        $sql = "DELETE FROM bet_tracker WHERE bet_id = :bet_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':bet_id', $betId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Calculates summary performance metrics for an authenticated user (ROI, Win Rate, Net Profit).
     */
    public function getUserPerformanceMetrics(int $userId): array
    {
        $bets = $this->getByUserId($userId);

        $totalBets = count($bets);
        $totalStaked = 0.0;
        $totalReturned = 0.0;
        $wonBets = 0;
        $lostBets = 0;
        $pendingBets = 0;
        $voidBets = 0;

        foreach ($bets as $bet) {
            $stake = (float) $bet['stake_amount'];
            $odds = (float) $bet['taken_odds'];
            $status = $bet['status'];

            if ($status === 'won') {
                $wonBets++;
                $totalStaked += $stake;
                $totalReturned += ($stake * $odds);
            } elseif ($status === 'lost') {
                $lostBets++;
                $totalStaked += $stake;
            } elseif ($status === 'pending') {
                $pendingBets++;
            } elseif ($status === 'void') {
                $voidBets++;
                // In void/push, the stake is refunded
                $totalStaked += $stake;
                $totalReturned += $stake;
            }
        }

        $settledBets = $wonBets + $lostBets;
        $netProfit = $totalReturned - $totalStaked;
        $winRate = $settledBets > 0 ? round(($wonBets / $settledBets) * 100.0, 2) : 0.0;
        $roi = $totalStaked > 0 ? round(($netProfit / $totalStaked) * 100.0, 2) : 0.0;

        return [
            'total_bets'    => $totalBets,
            'settled_bets'  => $settledBets,
            'won_bets'      => $wonBets,
            'lost_bets'     => $lostBets,
            'pending_bets'  => $pendingBets,
            'void_bets'     => $voidBets,
            'win_rate'      => $winRate,
            'total_staked'  => round($totalStaked, 2),
            'total_returned'=> round($totalReturned, 2),
            'net_profit'    => round($netProfit, 2),
            'roi_percentage'=> $roi
        ];
    }
}

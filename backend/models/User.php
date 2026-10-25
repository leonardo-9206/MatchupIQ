<?php
/**
 * User Model
 * Handles database operations for the users table using PDO Prepared Statements.
 * Complies with 3NF schema and OWASP security guidelines (zero SQL injection).
 */

require_once __DIR__ . '/../config/Database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find a user by their email address, including their role name.
     *
     * @param string $email
     * @return array|null Returns user record array if found, null otherwise
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT u.user_id, u.role_id, u.full_name, u.email, u.password_hash, u.created_at, r.role_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.role_id
                WHERE u.email = :email
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a user by their primary key ID.
     *
     * @param int $userId
     * @return array|null
     */
    public function findById(int $userId): ?array
    {
        $sql = "SELECT u.user_id, u.role_id, u.full_name, u.email, u.created_at, r.role_name
                FROM users u
                INNER JOIN roles r ON u.role_id = r.role_id
                WHERE u.user_id = :user_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Check if an email is already registered in the system.
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT 1 FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Create a new user with hashed password and assigned role.
     *
     * @param string $fullName
     * @param string $email
     * @param string $passwordHash
     * @param int $roleId Default 2 ('user')
     * @return int Inserted user_id
     */
    public function create(string $fullName, string $email, string $passwordHash, int $roleId = 2): int
    {
        $sql = "INSERT INTO users (role_id, full_name, email, password_hash, created_at)
                VALUES (:role_id, :full_name, :email, :password_hash, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':full_name', $fullName, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password_hash', $passwordHash, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }
}

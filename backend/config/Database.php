<?php
/**
 * Database Connection Service (Singleton Pattern)
 * Provides a secure, persistent PDO connection to MySQL.
 * Implements Fail-Fast environment validation for critical database variables.
 */

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private function __construct()
    {
        $this->loadEnvironment();

        // Fail-Fast validation: Ensure critical database parameters exist in environment
        $requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER'];
        foreach ($requiredVars as $var) {
            $val = getenv($var);
            if ($val === false || trim($val) === '') {
                throw new RuntimeException("Error crítico: Configuración de base de datos incompleta en .env");
            }
        }

        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // SSL support for TiDB Cloud Serverless or cloud databases
        $sslRequired = getenv('DB_SSL') === 'true' || getenv('TIDB_HOST') || (int)$port === 4000;
        if ($sslRequired) {
            $caPaths = [
                '/etc/ssl/certs/ca-certificates.crt', // Debian / Ubuntu (Render Docker runtime)
                '/etc/pki/tls/certs/ca-bundle.crt',   // RedHat / CentOS / Alpine
                '/etc/ssl/cert.pem'                    // macOS / OpenSSL standard
            ];
            foreach ($caPaths as $ca) {
                if (file_exists($ca)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
                    break;
                }
            }
            if (!isset($options[PDO::MYSQL_ATTR_SSL_CA])) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        try {
            $this->connection = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            throw new Exception('Database connection failed. Please check server configuration.');
        }
    }

    /**
     * Prevent cloning of the singleton instance.
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the singleton instance.
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }

    /**
     * Retrieve the singleton instance of the Database.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retrieve the active PDO connection object.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Simple parser to load key=value variables from .env if present.
     */
    private function loadEnvironment(): void
    {
        $envPath = __DIR__ . '/../.env';
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");

                if (getenv($key) === false) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

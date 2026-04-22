<?php
/**
 * Al-Riaz Associates — PDO Database Singleton
 * Provides a single shared PDO connection throughout the request lifecycle.
 */

require_once __DIR__ . '/../config/config.php';

final class Database
{
    private static ?PDO $instance = null;

    /** Prevent direct instantiation */
    private function __construct() {}

    /** Prevent cloning */
    private function __clone() {}

    /**
     * Return the shared PDO instance, creating it on first call.
     *
     * @throws RuntimeException if connection fails
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_NAME
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Log detailed error server-side; expose a generic message to the client
                error_log('[DB] Connection failed: ' . $e->getMessage());
                throw new RuntimeException('Database connection error. Please try again later.');
            }
        }

        return self::$instance;
    }
}

/**
 * Convenience helper so callers can write db() instead of Database::getInstance().
 */
function db(): PDO
{
    return Database::getInstance();
}

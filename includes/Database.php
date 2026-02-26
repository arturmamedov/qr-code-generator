<?php
/**
 * Database Class
 *
 * Handles all database operations using PDO with prepared statements.
 * Supports MySQL and SQLite backends via DB_DRIVER config constant.
 *
 * The $types parameter is kept in method signatures for backward compatibility
 * with existing call sites but is ignored internally — PDO infers types.
 */

class Database {
    private $connection;
    private $driver;
    private static $instance = null;

    /**
     * Private constructor (Singleton pattern)
     */
    private function __construct() {
        $this->driver = defined('DB_DRIVER') ? DB_DRIVER : 'mysql';
        $this->connect();
    }

    /**
     * Get singleton instance
     *
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect() {
        try {
            if ($this->driver === 'sqlite') {
                $dbPath = defined('DB_SQLITE_PATH') ? DB_SQLITE_PATH : ROOT_PATH . '/data/qr_codes.db';

                // Ensure directory exists
                $dir = dirname($dbPath);
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }

                $this->connection = new PDO('sqlite:' . $dbPath);
                $this->connection->exec('PRAGMA foreign_keys = ON');
                $this->connection->exec('PRAGMA journal_mode = WAL');
            } else {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
            }

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            logError("Database connection failed: " . $e->getMessage());
            die("Database connection error. Please check logs.");
        }
    }

    /**
     * Get PDO connection object
     *
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Get the active database driver name
     *
     * @return string 'mysql' or 'sqlite'
     */
    public function getDriver() {
        return $this->driver;
    }

    /**
     * Execute a prepared statement query
     *
     * @param string $sql SQL query with placeholders
     * @param string $types Kept for backward compatibility (ignored by PDO)
     * @param array $params Array of parameters
     * @return PDOStatement|false
     */
    public function query($sql, $types = "", $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;

        } catch (PDOException $e) {
            logError("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    /**
     * Insert record and return the last inserted ID
     *
     * @param string $sql SQL insert query
     * @param string $types Kept for backward compatibility (ignored by PDO)
     * @param array $params Parameters
     * @return int|false Insert ID or false on failure
     */
    public function insert($sql, $types = "", $params = []) {
        $stmt = $this->query($sql, $types, $params);

        if ($stmt) {
            return (int)$this->connection->lastInsertId();
        }

        return false;
    }

    /**
     * Update/Delete query - returns number of affected rows
     *
     * @param string $sql SQL query
     * @param string $types Kept for backward compatibility (ignored by PDO)
     * @param array $params Parameters
     * @return int|false Number of affected rows or false
     */
    public function execute($sql, $types = "", $params = []) {
        $stmt = $this->query($sql, $types, $params);

        if ($stmt) {
            return $stmt->rowCount();
        }

        return false;
    }

    /**
     * Fetch single row as associative array
     *
     * @param string $sql SQL query
     * @param string $types Kept for backward compatibility (ignored by PDO)
     * @param array $params Parameters
     * @return array|null Row data or null
     */
    public function fetchOne($sql, $types = "", $params = []) {
        $stmt = $this->query($sql, $types, $params);

        if ($stmt) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        }

        return null;
    }

    /**
     * Fetch all rows as array of associative arrays
     *
     * @param string $sql SQL query
     * @param string $types Kept for backward compatibility (ignored by PDO)
     * @param array $params Parameters
     * @return array Array of rows
     */
    public function fetchAll($sql, $types = "", $params = []) {
        $stmt = $this->query($sql, $types, $params);

        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * Escape string for safe SQL usage
     * Note: Prefer prepared statements. This method exists for backward compatibility.
     *
     * @param string $value Value to escape
     * @return string Escaped value
     */
    public function escape($value) {
        $quoted = $this->connection->quote($value);
        // PDO::quote() adds surrounding quotes; strip them for drop-in compatibility
        return substr($quoted, 1, -1);
    }

    /**
     * Check if a table exists (works for both MySQL and SQLite)
     *
     * @param string $tableName Table name to check
     * @return bool True if table exists
     */
    public function tableExists($tableName) {
        if ($this->driver === 'sqlite') {
            $result = $this->fetchOne(
                "SELECT name FROM sqlite_master WHERE type='table' AND name=?",
                "",
                [$tableName]
            );
        } else {
            $result = $this->fetchOne("SHOW TABLES LIKE ?", "", [$tableName]);
        }
        return $result !== null;
    }

    /**
     * Begin a transaction
     *
     * @return bool True on success, false on failure
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool True on success, false on failure
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool True on success, false on failure
     */
    public function rollback() {
        return $this->connection->rollBack();
    }

    /**
     * Close database connection
     */
    public function close() {
        $this->connection = null;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>

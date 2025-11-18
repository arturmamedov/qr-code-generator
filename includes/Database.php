<?php
/**
 * Database Class
 *
 * Handles all database operations using MySQLi with prepared statements
 * for security and error handling with logging.
 */

class Database {
    private $connection;
    private static $instance = null;

    /**
     * Private constructor (Singleton pattern)
     */
    private function __construct() {
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
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );

            // Check for connection errors
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }

            // Set charset to utf8mb4 for full Unicode support
            $this->connection->set_charset("utf8mb4");

        } catch (Exception $e) {
            logError("Database connection failed: " . $e->getMessage());
            die("Database connection error. Please check logs.");
        }
    }

    /**
     * Get MySQLi connection object
     *
     * @return mysqli
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute a prepared statement query
     *
     * @param string $sql SQL query with placeholders
     * @param string $types Parameter types (e.g., "ssi" for string, string, int)
     * @param array $params Array of parameters
     * @return mysqli_stmt|false
     */
    public function query($sql, $types = "", $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            // Bind parameters if provided
            if (!empty($types) && !empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            // Execute statement
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            return $stmt;

        } catch (Exception $e) {
            logError("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    /**
     * Insert record and return the last inserted ID
     *
     * @param string $sql SQL insert query
     * @param string $types Parameter types
     * @param array $params Parameters
     * @return int|false Insert ID or false on failure
     */
    public function insert($sql, $types, $params) {
        $stmt = $this->query($sql, $types, $params);

        if ($stmt) {
            $insertId = $this->connection->insert_id;
            $stmt->close();
            return $insertId;
        }

        return false;
    }

    /**
     * Update/Delete query - returns number of affected rows
     *
     * @param string $sql SQL query
     * @param string $types Parameter types
     * @param array $params Parameters
     * @return int|false Number of affected rows or false
     */
    public function execute($sql, $types, $params) {
        $stmt = $this->query($sql, $types, $params);

        if ($stmt) {
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            return $affectedRows;
        }

        return false;
    }

    /**
     * Fetch single row as associative array
     *
     * @param string $sql SQL query
     * @param string $types Parameter types
     * @param array $params Parameters
     * @return array|null Row data or null
     */
    public function fetchOne($sql, $types = "", $params = []) {
        $stmt = $this->query($sql, $types, $params);

        if ($stmt) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row;
        }

        return null;
    }

    /**
     * Fetch all rows as array of associative arrays
     *
     * @param string $sql SQL query
     * @param string $types Parameter types
     * @param array $params Parameters
     * @return array Array of rows
     */
    public function fetchAll($sql, $types = "", $params = []) {
        $stmt = $this->query($sql, $types, $params);

        if ($stmt) {
            $result = $stmt->get_result();
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        }

        return [];
    }

    /**
     * Escape string for safe SQL usage
     *
     * @param string $value Value to escape
     * @return string Escaped value
     */
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }

    /**
     * Close database connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
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

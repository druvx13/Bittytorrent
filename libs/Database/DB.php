<?php

namespace App\Database;

use PDO;
use PDOException;
use PDOStatement;
use stdClass;

class DB
{
    private static ?DB $instance = null;
    private PDO $connection;
    private array $lastResult = [];
    private ?int $numRows = null;
    private ?int $affectedRows = null;
    private ?string $lastError = null;
    private ?string $lastQuery = null;

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? 3306;
        $dbname = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw new \Exception("Database connection error: " . $this->lastError);
        }
    }

    public static function getInstance(): DB
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function query(string $sql, array $params = []): bool|int
    {
        $this->lastQuery = $sql;
        $this->lastResult = [];
        $this->numRows = 0;
        $this->affectedRows = 0;
        $this->lastError = null;

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $sql)) {
                $this->lastResult = $stmt->fetchAll();
                $this->numRows = count($this->lastResult);
                return $this->numRows;
            } else {
                $this->affectedRows = $stmt->rowCount();
                return $this->affectedRows;
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            // Log error or handle it as per old behavior (maybe trigger error?)
            return false;
        }
    }

    public function get_results(string $sql, string $output = 'OBJECT'): ?array
    {
        $this->query($sql);

        if (empty($this->lastResult)) {
            return null;
        }

        if ($output === 'ARRAY_A') {
            return array_map(function ($row) {
                return (array) $row;
            }, $this->lastResult);
        }

        if ($output === 'ARRAY_N') {
            return array_map(function ($row) {
                return array_values((array) $row);
            }, $this->lastResult);
        }

        return $this->lastResult;
    }

    public function get_row(string $sql, string $output = 'OBJECT', int $offset = 0): mixed
    {
        $this->query($sql);

        if (!isset($this->lastResult[$offset])) {
            return null;
        }

        $row = $this->lastResult[$offset];

        if ($output === 'ARRAY_A') {
            return (array) $row;
        }

        if ($output === 'ARRAY_N') {
            return array_values((array) $row);
        }

        return $row;
    }

    public function get_var(string $sql, int $x = 0, int $y = 0): mixed
    {
        $this->query($sql);

        if (!isset($this->lastResult[$y])) {
            return null;
        }

        $row = (array) $this->lastResult[$y];
        $values = array_values($row);

        return $values[$x] ?? null;
    }

    public function escape(string $string): string
    {
        // PDO doesn't expose escape_string directly, but quote() adds quotes.
        // We need to remove the surrounding quotes for compatibility with old escape() which just escapes logic.
        // However, standard practice with PDO is using prepared statements.
        // The old code uses $db->escape() manually in SQL strings.
        // We can use $pdo->quote() and strip the outer quotes.

        $quoted = $this->connection->quote($string);
        if (str_starts_with($quoted, "'") && str_ends_with($quoted, "'")) {
            return substr($quoted, 1, -1);
        }
        return $quoted;
    }

    public function insert_id(): int|string
    {
        return $this->connection->lastInsertId();
    }

    // Helper to allow property access for compatibility
    public function __get($name)
    {
        if ($name === 'insert_id') {
            return $this->insert_id();
        }
        if ($name === 'last_error') {
            return $this->lastError;
        }
        return null;
    }

}

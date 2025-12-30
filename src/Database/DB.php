<?php
namespace BittyTorrent\Database;

use PDO;
use PDOException;

class DB {
    private $pdo;
    private $lastError;
    private $lastQuery;

    public function __construct($host, $user, $pass, $name, $port = 3306) {
        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            throw $e;
        }
    }

    public function escape($str) {
        return substr($this->pdo->quote($str), 1, -1);
    }

    public function query($query, $params = []) {
        $this->lastQuery = $query;
        try {
            if (empty($params)) {
                 if (stripos($query, 'SELECT') === 0 || stripos($query, 'SHOW') === 0) {
                     $stmt = $this->pdo->query($query);
                     return $stmt->fetchAll();
                 } else {
                     return $this->pdo->exec($query);
                 }
            } else {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
                if (stripos($query, 'SELECT') === 0 || stripos($query, 'SHOW') === 0) {
                     return $stmt->fetchAll();
                 } else {
                     return $stmt->rowCount();
                 }
            }
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            echo "DB Error: " . $e->getMessage() . " Query: " . $query;
            return false;
        }
    }

    public function get_results($query, $output = 'OBJECT') {
        $results = $this->query($query);
        if (!is_array($results)) return [];

        if ($output === 'ARRAY_A') {
             return array_map(function($row) { return (array)$row; }, $results);
        }
        return $results;
    }

    public function get_row($query, $output = 'OBJECT', $y = 0) {
        $results = $this->query($query);
         if (!is_array($results) || !isset($results[$y])) return null;

         $row = $results[$y];
         if ($output === 'ARRAY_A') {
             return (array)$row;
         }
         return $row;
    }

    public function get_var($query) {
        $results = $this->query($query);
        if (!is_array($results) || empty($results)) return null;

        $row = (array)$results[0];
        return array_values($row)[0];
    }

    public function insert_id() {
        return $this->pdo->lastInsertId();
    }
}

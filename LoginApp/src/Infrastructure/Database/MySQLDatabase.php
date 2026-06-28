<?php
namespace LoginApp\Infrastructure\Database;

use mysqli;
use mysqli_result;
use LoginApp\Application\Config\Config;

class MySQLDatabase implements DatabaseInterface {
    
    private mysqli $connection;

    public function __construct(?mysqli $connection = null) {
        if ($connection) {
            $this->connection = $connection;
        } else {
            $this->connect();
        }
    }

    private function connect(): void {
        $config = Config::getInstance();
        
        $this->connection = new mysqli(
            $config->get('DB_HOST'),
            $config->get('DB_USER'),
            $config->get('DB_PASSWORD'),
            $config->get('DB_NAME'),
            $config->get('DB_PORT')
        );

        if ($this->connection->connect_error) {
            throw new \RuntimeException('Database connection failed: ' . $this->connection->connect_error);
        }

        $this->connection->set_charset('utf8mb4');
    }

    public function select(string $query, string $types = '', ...$params): ?mysqli_result {
        $stmt = $this->connection->prepare($query);
        
        if (!$stmt) {
            error_log('Query prepare failed: ' . $this->connection->error);
            return null;
        }

        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log('Query execution failed: ' . $stmt->error);
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result();
        $stmt->close();

        return $result;
    }

    public function insert(string $query, string $types = '', ...$params): int {
        $stmt = $this->connection->prepare($query);
        
        if (!$stmt) {
            error_log('Insert prepare failed: ' . $this->connection->error);
            return -1;
        }

        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log('Insert execution failed: ' . $stmt->error);
            $stmt->close();
            return -1;
        }

        $insertId = $this->connection->insert_id;
        $stmt->close();

        return $insertId;
    }

    public function update(string $query, string $types = '', ...$params): bool {
        $stmt = $this->connection->prepare($query);
        
        if (!$stmt) {
            error_log('Update prepare failed: ' . $this->connection->error);
            return false;
        }

        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function delete(string $query, string $types = '', ...$params): bool {
        return $this->update($query, $types, ...$params);
    }

    public function close(): void {
        $this->connection->close();
    }

    public function getConnection(): mysqli {
        return $this->connection;
    }
}
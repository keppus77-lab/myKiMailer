<?php

declare(strict_types=1);

namespace LoginApp\Infrastructure\Repositories;

use LoginApp\Domain\Repositories\UserRepositoryInterface;
use LoginApp\Domain\Entities\User;
use LoginApp\Infrastructure\Database\DatabaseInterface;

class UserRepository implements UserRepositoryInterface {
    
    private DatabaseInterface $database;

    public function __construct(DatabaseInterface $database) {
        $this->database = $database;
    }

    public function findByUsername(string $username): ?User {
        $result = $this->database->select(
            'SELECT id, password, name, email, verified FROM users WHERE name = ?',
            's',
            $name
        );

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free_result();

        return $this->mapRowToUser($row);
    }

    public function findById(int $id): ?User {
        $result = $this->database->select(
            'SELECT id, name, password, email, verified FROM users WHERE id = ?',
            'i',
            $id
        );

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free_result();

        return $this->mapRowToUser($row);
    }

    public function findByEmail(string $email): ?User {
        $result = $this->database->select(
            'SELECT id, password, name, email, verified FROM users WHERE email = ?',
            's',
            $email
        );

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free_result();

        return $this->mapRowToUser($row);
    }
                    
    public function findByEmailWithRequestCount(string $email, int $sinceTimestamp): ?array {
        $result = $this->database->select(
            'SELECT users.id, password, name, email, verified, COUNT(requests.id) as request_count 
             FROM users 
             LEFT JOIN requests ON users.id = requests.user AND type=0 AND timestamp>? 
             WHERE email=? 
             GROUP BY users.id',
            'is',
            $sinceTimestamp,
            $email
        );

        if (!$result || $result->num_rows !== 1) {
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free_result();

        return [
            'user' => $this->mapRowToUser($row),
            'request_count' => (int)$row['request_count']
        ];
    }
                    
    public function findByEmailWithLoginAttempts(string $email, int $sinceTimestamp): ?array {
        // DEBUG: Query anzeigen
        $query = 'SELECT users.id, password, name, email, verified, COUNT(loginattempts.id) as attempt_count 
            FROM users 
            LEFT JOIN loginattempts ON users.id = loginattempts.user AND loginattempts.timestamp > ? 
            WHERE email = ? 
            GROUP BY users.id';
        
        error_log('=== findByEmailWithLoginAttempts Debug ===');
        error_log('Query: ' . $query);
        error_log('Email: ' . $email);
        error_log('Since Timestamp: ' . $sinceTimestamp);
        
        try {
            $result = $this->database->select(
                $query,
                'is',
                $sinceTimestamp,
                $email
            );

            error_log('Result: ' . var_export($result, true));

            if (!$result || $result->num_rows !== 1) {
                error_log('No result or multiple results');
                return null;
            }

            $row = $result->fetch_assoc();
            error_log('Row data: ' . json_encode($row));
            
            $result->free_result();

            return [
                'user' => $this->mapRowToUser($row),
                'attempt_count' => (int)$row['attempt_count']
            ];
        } catch (\Exception $e) {
            error_log('Exception in findByEmailWithLoginAttempts: ' . $e->getMessage());
            throw $e;
        }
    }


    public function create(string $name, string $email, string $passwordHash): int {
        return $this->database->insert(
            'INSERT INTO users VALUES (NULL, ?, ?, ?, 0)',
            'sss',
            $name,
            $email,
            $passwordHash
        );
    }

    private function mapRowToUser(array $row): User {
        return new User(
            (int)$row['id'],
            $row['username'] ?? '',
            $row['password'],
            $row['name'] ?? '',
            $row['email'] ?? '',
            (bool)($row['verified'] ?? false)
        );
    }
}
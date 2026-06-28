<?php
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
            'SELECT id, username, password, name, email, verified FROM users WHERE username = ?',
            's',
            $username
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
            'SELECT id, username, password, name, email, verified FROM users WHERE id = ?',
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
            'SELECT id, username, password, name, email, verified FROM users WHERE email = ?',
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
            'SELECT users.id, username, password, name, email, verified, COUNT(requests.id) as request_count 
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
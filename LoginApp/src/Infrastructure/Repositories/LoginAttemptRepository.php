<?php

declare(strict_types=1);

namespace LoginApp\Infrastructure\Repositories;

use LoginApp\Domain\Repositories\LoginAttemptRepositoryInterface;
use LoginApp\Infrastructure\Database\DatabaseInterface;

class LoginAttemptRepository implements LoginAttemptRepositoryInterface {
    
    private DatabaseInterface $database;

    public function __construct(DatabaseInterface $database) {
        $this->database = $database;
    }

    public function create(int $userId, string $ipAddress, int $timestamp): int {
        return $this->database->insert(
            'INSERT INTO loginattempts VALUES (NULL, ?, ?, ?)',
            'isi',
            $userId,
            $ipAddress,
            $timestamp
        );
    }

    public function countRecentAttempts(int $userId, int $sinceTimestamp): int {
        $result = $this->database->select(
            'SELECT COUNT(*) as count FROM loginattempts WHERE user=? AND timestamp>?',
            'ii',
            $userId,
            $sinceTimestamp
        );

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        $result->free_result();

        return (int)$row['count'];
    }

    public function deleteAllForUser(int $userId): bool {
        return $this->database->delete(
            'DELETE FROM loginattempts WHERE user=?',
            'i',
            $userId
        );
    }
}
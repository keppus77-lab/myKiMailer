<?php
namespace LoginApp\Infrastructure\Repositories;

use LoginApp\Domain\Repositories\EmailVerificationRequestRepositoryInterface;
use LoginApp\Domain\Entities\EmailVerificationRequest;
use LoginApp\Infrastructure\Database\DatabaseInterface;

class EmailVerificationRequestRepository implements EmailVerificationRequestRepositoryInterface {
    
    private DatabaseInterface $database;

    public function __construct(DatabaseInterface $database) {
        $this->database = $database;
    }

    public function create(EmailVerificationRequest $request): int {
        return $this->database->insert(
            'INSERT INTO requests (user, token_hash, timestamp, type) VALUES (?, ?, ?, ?)',
            'isis',
            $request->getUserId(),
            $request->getTokenHash(),
            $request->getTimestamp(),
            $request->getType()
        );
    }

    public function countRecentRequestsForUser(int $userId, int $sinceTimestamp, int $type = 0): int {
        $result = $this->database->select(
            'SELECT COUNT(*) as count FROM requests WHERE user = ? AND type = ? AND timestamp > ?',
            'iii',
            $userId,
            $type,
            $sinceTimestamp
        );

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        $result->free_result();

        return (int)$row['count'];
    }

    public function findById(int $id): ?EmailVerificationRequest {
        $result = $this->database->select(
            'SELECT id, user, token_hash, timestamp, type FROM requests WHERE id = ?',
            'i',
            $id
        );

        if (!$result || $result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();
        $result->free_result();

        return $this->mapRowToRequest($row);
    }

    public function delete(int $id): bool {
        return $this->database->delete(
            'DELETE FROM requests WHERE id = ?',
            'i',
            $id
        );
    }

    public function deleteAllForUser(int $userId, int $type = 0): bool {
        return $this->database->delete(
            'DELETE FROM requests WHERE user = ? AND type = ?',
            'ii',
            $userId,
            $type
        );
    }

    public function findExpired(int $expirationTimestamp): array {
        $result = $this->database->select(
            'SELECT id, user, token_hash, timestamp, type FROM requests WHERE timestamp < ?',
            'i',
            $expirationTimestamp
        );

        if (!$result) {
            return [];
        }

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $this->mapRowToRequest($row);
        }

        $result->free_result();

        return $requests;
    }

    public function deleteExpired(int $expirationTimestamp): bool {
        return $this->database->delete(
            'DELETE FROM requests WHERE timestamp < ?',
            'i',
            $expirationTimestamp
        );
    }

    private function mapRowToRequest(array $row): EmailVerificationRequest {
        return new EmailVerificationRequest(
            (int)$row['id'],
            (int)$row['user'],
            $row['token_hash'],
            (int)$row['timestamp'],
            (int)($row['type'] ?? 0)
        );
    }
}
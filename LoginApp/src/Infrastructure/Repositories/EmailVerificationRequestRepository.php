<?php
namespace LoginApp\Infrastructure\Repositories;

use LoginApp\Domain\Repositories\EmailVerificationRequestRepositoryInterface;
use LoginApp\Domain\Entities\EmailVerificationRequest;
use LoginApp\Application\Controllers\LoginController;

class EmailVerificationRequestRepository implements EmailVerificationRequestRepositoryInterface {
    
    private LoginController $connection;

    public function __construct(LoginController $connection) {
        $this->connection = $connection;
    }

    public function create(EmailVerificationRequest $request): int {
        return $this->connection->sqlInsert(
            'INSERT INTO requests VALUES (NULL, ?, ?, ?, ?)',
            'isis',
            $request->getUserId(),
            $request->getTokenHash(),
            $request->getTimestamp(),
            $request->getType()
        );
    }

    public function countRecentRequestsForUser(int $userId, int $sinceTimestamp, int $type = 0): int {
        $res = $this->connection->sqlSelect(
            'SELECT COUNT(*) as count FROM requests WHERE user=? AND type=? AND timestamp>?',
            'iii',
            $userId,
            $type,
            $sinceTimestamp
        );

        if (!$res) {
            return 0;
        }

        $row = $res->fetch_assoc();
        $res->free_result();

        return (int)$row['count'];
    }
}
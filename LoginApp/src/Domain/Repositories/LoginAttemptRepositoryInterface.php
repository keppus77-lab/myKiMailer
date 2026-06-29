<?php

declare(strict_types=1);

namespace LoginApp\Domain\Repositories;

use LoginApp\Domain\Entities\LoginAttempt;

interface LoginAttemptRepositoryInterface {
    public function create(int $userId, string $ipAddress, int $timestamp): int;
    public function countRecentAttempts(int $userId, int $sinceTimestamp): int;
    public function deleteAllForUser(int $userId): bool;
}
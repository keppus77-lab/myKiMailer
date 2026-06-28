<?php

declare(strict_types=1); 

namespace LoginApp\Domain\Repositories;

use LoginApp\Domain\Entities\EmailVerificationRequest;

interface EmailVerificationRequestRepositoryInterface {
    public function create(EmailVerificationRequest $request): int;
    public function countRecentRequestsForUser(int $userId, int $sinceTimestamp, int $type = 0): int;
}
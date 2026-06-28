<?php

declare(strict_types=1);

namespace LoginApp\Domain\Repositories;

use LoginApp\Domain\Entities\User;

interface UserRepositoryInterface {
    public function findByEmailWithRequestCount(string $email, int $sinceTimestamp): ?array;
}
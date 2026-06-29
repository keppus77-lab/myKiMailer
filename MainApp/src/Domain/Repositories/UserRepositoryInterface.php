<?php

declare(strict_types=1);

namespace MainApp\Domain\Repositories;

use MainApp\Domain\Entities\User;

interface UserRepositoryInterface {
    public function findByEmailWithRequestCount(string $email, int $sinceTimestamp): ?array;
}
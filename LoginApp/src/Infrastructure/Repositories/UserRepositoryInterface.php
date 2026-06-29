<?php

declare(strict_types=1);

namespace LoginApp\Domain\Repositories;

use LoginApp\Domain\Entities\User;

interface UserRepositoryInterface {
    public function findByUsername(string $username): ?User;
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByEmailWithRequestCount(string $email, int $sinceTimestamp): ?array;
    public function findByEmailWithLoginAttempts(string $email, int $sinceTimestamp): ?array;
    public function create(string $name, string $email, string $passwordHash): int;
}
<?php

declare(strict_types=1);
namespace LoginApp\Domain\Services;

interface JwtServiceInterface {
    public function encode(array $payload): string;
    public function decode(string $token): ?array;
    public function verify(string $token): bool;
}
<?php

declare(strict_types=1);

namespace LoginApp\Domain\ValueObjects;

class JwtPayload {
    private int $issuedAt;
    private int $expiresAt;
    private int $userId;
    private string $email;

    public function __construct(int $userId, string $email, int $expirationSeconds = 3600) {
        $now = time();
        $this->issuedAt = $now;
        $this->expiresAt = $now + $expirationSeconds;
        $this->userId = $userId;
        $this->email = $email;
    }

    public function toArray(): array {
        return [
            'iat' => $this->issuedAt,
            'exp' => $this->expiresAt,
            'sub' => $this->userId,
            'email' => $this->email
        ];
    }

    public function getIssuedAt(): int {
        return $this->issuedAt;
    }

    public function getExpiresAt(): int {
        return $this->expiresAt;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getEmail(): string {
        return $this->email;
    }
}
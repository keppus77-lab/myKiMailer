<?php

declare(strict_types=1);

namespace MainApp\Domain\ValueObjects;

class UserSession {
    private bool $isAuthenticated;
    private ?int $userId;

    private function __construct(bool $isAuthenticated, ?int $userId) {
        $this->isAuthenticated = $isAuthenticated;
        $this->userId = $userId;
    }

    public static function authenticated(int $userId): self {
        return new self(true, $userId);
    }

    public static function guest(): self {
        return new self(false, null);
    }

    public function isAuthenticated(): bool {
        return $this->isAuthenticated;
    }

    public function getUserId(): ?int {
        return $this->userId;
    }

    public function toArray(): array {
        return [
            'loggedin' => $this->isAuthenticated,
            'userid' => $this->userId
        ];
    }
}
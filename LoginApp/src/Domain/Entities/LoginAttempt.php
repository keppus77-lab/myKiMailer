<?php

declare(strict_types=1);

namespace LoginApp\Domain\Entities;

class LoginAttempt {
    private ?int $id;
    private int $userId;
    private string $ipAddress;
    private int $timestamp;

    public function __construct(?int $id, int $userId, string $ipAddress, int $timestamp) {
        $this->id = $id;
        $this->userId = $userId;
        $this->ipAddress = $ipAddress;
        $this->timestamp = $timestamp;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getIpAddress(): string {
        return $this->ipAddress;
    }

    public function getTimestamp(): int {
        return $this->timestamp;
    }
}
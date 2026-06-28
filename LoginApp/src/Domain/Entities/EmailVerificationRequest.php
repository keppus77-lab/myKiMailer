<?php

declare(strict_types=1);

namespace LoginApp\Domain\Entities;

class EmailVerificationRequest {
    private ?int $id;
    private int $userId;
    private string $tokenHash;
    private int $timestamp;
    private int $type;

    public function __construct(?int $id, int $userId, string $tokenHash, int $timestamp, int $type = 0) {
        $this->id = $id;
        $this->userId = $userId;
        $this->tokenHash = $tokenHash;
        $this->timestamp = $timestamp;
        $this->type = $type;
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getTokenHash(): string { return $this->tokenHash; }
    public function getTimestamp(): int { return $this->timestamp; }
    public function getType(): int { return $this->type; }
}
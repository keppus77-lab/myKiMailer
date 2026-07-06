<?php

declare(strict_types=1);

namespace MainApp\Domain\Entities;

class ImapAccount {
    
    private int $id;
    private int $userId;
    private string $email;
    private string $host;
    private int $port;
    private bool $use_ssl;
    private string $encryptedPassword;
    private string $encryption; // ssl, tls, none
    private bool $active;
    private ?\DateTime $createdAt;
    private ?\DateTime $lastChecked;

    public function __construct(
        int $id,
        int $userId,
        string $email,
        string $host,
        int $port,
        string $encryptedPassword,
        string $encryption,
        bool $use_ssl,
        bool $active = true,
        ?\DateTime $createdAt = null,
        ?\DateTime $lastChecked = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->email = $email;
        $this->host = $host;
        $this->port = $port;
        $this->encryptedPassword = $encryptedPassword;
        $this->encryption = $encryption;
        $this->use_ssl = $use_ssl;
        $this->active = $active;
        $this->createdAt = $createdAt;
        $this->lastChecked = $lastChecked;
    }

    // Getters
    public function getId(): int {
        return $this->id;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getPort(): int {
        return $this->port;
    }

        public function getUseSsl(): bool {
        return $this->use_ssl;
    }

    public function getEncryptedPassword(): string {
        return $this->encryptedPassword;
    }

    public function getEncryption(): string {
        return $this->encryption;
    }

    public function isActive(): bool {
        return $this->active;
    }

    public function getCreatedAt(): ?\DateTime {
        return $this->createdAt;
    }

    public function getLastChecked(): ?\DateTime {
        return $this->lastChecked;
    }

    /**
     * Convert to array (for JSON response, without password)
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'use_ssl' => $this->use_ssl,
            'active' => $this->active,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'last_checked' => $this->lastChecked?->format('Y-m-d H:i:s')
            // Kein Password in der Response!
        ];
    }
}
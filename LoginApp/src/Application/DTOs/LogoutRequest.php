<?php
declare(strict_types=1);


namespace LoginApp\Application\DTOs;

class LogoutRequest {
    public ?string $csrfToken;

    public function __construct(array $postData) {
        $this->csrfToken = $postData['csrf_token'] ?? null;
    }

    public function isValid(): bool {
        return $this->csrfToken !== null;
    }

    public function getCsrfToken(): string {
        if ($this->csrfToken === null) {
            throw new \RuntimeException('CSRF token is required');
        }
        return $this->csrfToken;
    }
}
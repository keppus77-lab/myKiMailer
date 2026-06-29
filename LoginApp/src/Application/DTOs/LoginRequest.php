<?php
declare(strict_types=1);

namespace LoginApp\Application\DTOs;

class LoginRequest {
    public ?string $email;
    public ?string $password;
    public ?string $csrfToken;

    public function __construct(array $postData) {
        $this->email = $postData['email'] ?? null;
        $this->password = $postData['password'] ?? null;
        $this->csrfToken = $postData['csrf_token'] ?? null;
    }

    public function hasRequiredFields(): bool {
        return $this->email !== null &&
               $this->password !== null &&
               $this->csrfToken !== null;
    }

    public function validate(): array {
        $errors = [];

        if ($this->email === null) {
            $errors[] = 'email_required';
        }

        if ($this->password === null) {
            $errors[] = 'password_required';
        }

        if ($this->csrfToken === null) {
            $errors[] = 'csrf_required';
        }

        return $errors;
    }
}
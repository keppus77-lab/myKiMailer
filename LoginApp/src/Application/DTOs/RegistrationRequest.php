<?php

declare(strict_types=1);

namespace LoginApp\Application\DTOs;

class RegistrationRequest {
    public ?string $name;
    public ?string $email;
    public ?string $password;
    public ?string $confirmPassword;
    public ?string $csrfToken;

    public function __construct(array $postData) {
        $this->name = $postData['name'] ?? null;
        $this->email = $postData['email'] ?? null;
        $this->password = $postData['password'] ?? null;
        $this->confirmPassword = $postData['confirm-password'] ?? null;
        $this->csrfToken = $postData['csrf_token'] ?? null;
    }

    public function validate(): array {
        $errors = [];

        if ($this->name === null) {
            $errors[] = 1;
        }

        if ($this->email === null) {
            $errors[] = 2;
        }

        if ($this->password === null) {
            $errors[] = 4;
        }

        if ($this->confirmPassword === null) {
            $errors[] = 5;
        }

        if ($this->csrfToken === null) {
            $errors[] = 9;
        }

        return $errors;
    }

    public function hasRequiredFields(): bool {
        return $this->name !== null &&
               $this->email !== null &&
               $this->password !== null &&
               $this->confirmPassword !== null &&
               $this->csrfToken !== null;
    }
}
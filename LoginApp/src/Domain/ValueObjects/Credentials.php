<?php

declare(strict_types=1);

namespace LoginApp\Domain\ValueObjects;

class Credentials {
    private string $username;
    private string $password;

    public function __construct(string $username, string $password) {
        $this->validateUsername($username);
        $this->validatePassword($password);
        
        $this->username = $username;
        $this->password = $password;
    }

    private function validateUsername(string $username): void {
        if (empty(trim($username))) {
            throw new \InvalidArgumentException('Username cannot be empty');
        }
    }

    private function validatePassword(string $password): void {
        if (empty($password)) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getPassword(): string {
        return $this->password;
    }
}
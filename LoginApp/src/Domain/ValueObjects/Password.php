<?php
namespace LoginApp\Domain\ValueObjects;

class Password {
    private string $value;

    public function __construct(string $password) {
        $this->validate($password);
        $this->value = $password;
    }

    private function validate(string $password): void {
        // At least 8 chars, 1 lowercase, 1 uppercase, 1 digit, 1 special char
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\~?!@#\$%\^&\*])(?=.{8,})/', $password)) {
            throw new \InvalidArgumentException('Password does not meet requirements', 4);
        }
    }

    public function getValue(): string {
        return $this->value;
    }

    public function hash(int $algorithm = PASSWORD_DEFAULT): string {
        return password_hash($this->value, $algorithm);
    }

    public function matches(string $confirmPassword): bool {
        return $this->value === $confirmPassword;
    }
}
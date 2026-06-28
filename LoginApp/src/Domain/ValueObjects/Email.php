<?php

declare(strict_types=1);

namespace LoginApp\Domain\ValueObjects;

class Email {
    private string $value;

    public function __construct(string $email) {
        $this->validate($email);
        $this->value = $email;
    }

    private function validate(string $email): void {
        if (strlen($email) > 255) {
            throw new \InvalidArgumentException('Email too long', 2);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format', 2);
        }

        $domain = substr($email, strpos($email, '@') + 1);
        if (!checkdnsrr($domain, 'MX')) {
            throw new \InvalidArgumentException('Invalid email domain', 3);
        }
    }

    public function getValue(): string {
        return $this->value;
    }

    public function __toString(): string {
        return $this->value;
    }
}
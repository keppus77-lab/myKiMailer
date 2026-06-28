<?php

declare(strict_types=1);

namespace LoginApp\Domain\ValueObjects;

class Name {
    private string $value;

    public function __construct(string $name) {
        $this->validate($name);
        $this->value = $name;
    }

    private function validate(string $name): void {
        if (strlen($name) > 255) {
            throw new \InvalidArgumentException('Name too long', 1);
        }

        if (!preg_match('/^[a-zA-Z- ]+$/', $name)) {
            throw new \InvalidArgumentException('Invalid name format', 1);
        }
    }

    public function getValue(): string {
        return $this->value;
    }

    public function __toString(): string {
        return $this->value;
    }
}
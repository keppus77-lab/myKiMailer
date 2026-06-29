<?php

declare(strict_types=1);

namespace LoginApp\Domain\ValueObjects;

class JwtToken {
    private string $value;

    public function __construct(string $token) {
        $this->value = $token;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function __toString(): string {
        return $this->value;
    }
}
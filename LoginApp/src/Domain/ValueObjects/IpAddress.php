<?php

declare(strict_types=1);

namespace LoginApp\Domain\ValueObjects;

class IpAddress {
    private string $value;

    public function __construct(string $ip) {
        $this->validate($ip);
        $this->value = $ip;
    }

    private function validate(string $ip): void {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address');
        }
    }

    public function getValue(): string {
        return $this->value;
    }

    public function __toString(): string {
        return $this->value;
    }

    public static function fromServer(): self {
        return new self($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
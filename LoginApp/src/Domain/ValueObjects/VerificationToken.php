<?php

declare(strict_types=1);

namespace LoginApp\Domain\ValueObjects;

class VerificationToken {
    private string $rawToken;

    private function __construct(string $rawToken) {
        $this->rawToken = $rawToken;
    }

    public static function generate(): self {
        return new self(random_bytes(32));
    }

    public function getRaw(): string {
        return $this->rawToken;
    }

    public function hash(string $algorithm): string {
        return password_hash($this->rawToken, $algorithm);
    }

    public function urlSafeEncode(): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($this->rawToken));
    }
}
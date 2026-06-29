<?php

declare(strict_types=1);

namespace LoginApp\Infrastructure\Services;

use LoginApp\Domain\Services\JwtServiceInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class FirebaseJwtService implements JwtServiceInterface {
    
    private string $secret;
    private string $algorithm;

    public function __construct(string $secret, string $algorithm = 'HS256') {
        $this->secret = $secret;
        $this->algorithm = $algorithm;
    }

    public function encode(array $payload): string {
        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function decode(string $token): ?array {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            error_log('JWT decode error: ' . $e->getMessage());
            return null;
        }
    }

    public function verify(string $token): bool {
        return $this->decode($token) !== null;
    }
}
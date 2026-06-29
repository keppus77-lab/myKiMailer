<?php

declare(strict_types=1);

namespace MainApp\Domain\Services;

interface CsrfTokenServiceInterface {
    public function validateToken(string $token): bool;
    public function generateToken(): string;
}
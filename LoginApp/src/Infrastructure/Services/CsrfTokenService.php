<?php

declare(strict_types=1);

namespace LoginApp\Infrastructure\Services;

use LoginApp\Domain\Services\CsrfTokenServiceInterface;
use LoginApp\Application\Services\SessionManagerInterface;

class CsrfTokenService implements CsrfTokenServiceInterface {
    
    private SessionManagerInterface $sessionManager;

    public function __construct(SessionManagerInterface $sessionManager) {
        $this->sessionManager = $sessionManager;
    }

    public function validateToken(string $token): bool {
        $sessionToken = $this->sessionManager->get('csrf_token');
        
        if (!$sessionToken) {
            return false;
        }

        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionToken, $token);
    }

    public function generateToken(): string {
        $token = bin2hex(random_bytes(32));
        $this->sessionManager->set('csrf_token', $token);
        return $token;
    }
}
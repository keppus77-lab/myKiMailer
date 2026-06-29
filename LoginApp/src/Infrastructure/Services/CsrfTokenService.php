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

        error_log('=== CSRF Validation Debug ===');
        error_log('Token from request: ' . $token);

        $sessionToken = $this->sessionManager->get('csrf_token');
        error_log('Token from session: ' . var_export($sessionToken, true));
        error_log('Session has csrf_token: ' . var_export($this->sessionManager->has('csrf_token'), true));
        
        
        if (!$sessionToken) {
            error_log('No session token found!');
            return false;
        }

        // Use hash_equals to prevent timing attacks
           $result = hash_equals($sessionToken, $token);
        error_log('Validation result: ' . var_export($result, true));
        error_log('=== End Debug ===');
        
        return $result;
    }

    public function generateToken(): string {
        $token = bin2hex(random_bytes(32));
        $this->sessionManager->set('csrf_token', $token);
        return $token;
    }
}
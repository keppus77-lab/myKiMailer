<?php
namespace MainApp\Application\UseCases;

use MainApp\Application\Services\SessionManagerInterface;
use MainApp\Application\Services\CookieManagerInterface;
use MainApp\Domain\Services\CsrfTokenServiceInterface;

class LogoutUseCase {
    
    private SessionManagerInterface $sessionManager;
    private CookieManagerInterface $cookieManager;
    private CsrfTokenServiceInterface $csrfService;

    public function __construct(
        SessionManagerInterface $sessionManager,
        CookieManagerInterface $cookieManager,
        CsrfTokenServiceInterface $csrfService
    ) {
        $this->sessionManager = $sessionManager;
        $this->cookieManager = $cookieManager;
        $this->csrfService = $csrfService;
    }

    /**
     * Logout user and clear session/cookies
     * 
     * @return bool True if logout successful, false if CSRF validation failed
     */
    public function execute(string $csrfToken): bool {
        // Validate CSRF token
        error_log("execute");
        if (!$this->csrfService->validateToken($csrfToken)) {
            error_log('LogoutUseCase: CSRF token validation failed');
            return false;
        }


        // Clear session
        error_log('Executing LogoutUseCase: Clearing session and cookies');

        $this->sessionManager->clear();
        $this->sessionManager->destroy();

        // Clear JWT cookie
        $this->cookieManager->delete('jwt_token', [
            'path' => '/',
            'httponly' => true,
            'secure' => true,
            'samesite' => 'Strict'
        ]);

        return true;
    }
}
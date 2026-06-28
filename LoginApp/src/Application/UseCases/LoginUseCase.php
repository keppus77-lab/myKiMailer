<?php
namespace LoginApp\Application\UseCases;

use LoginApp\Domain\Services\AuthenticationService;
use LoginApp\Domain\Services\AuthenticationException;
use LoginApp\Domain\ValueObjects\Credentials;
use LoginApp\Domain\ValueObjects\UserSession;
use LoginApp\Application\Services\SessionManagerInterface;

class LoginUseCase {
    
    private AuthenticationService $authService;
    private SessionManagerInterface $sessionManager;

    public function __construct(
        AuthenticationService $authService,
        SessionManagerInterface $sessionManager
    ) {
        $this->authService = $authService;
        $this->sessionManager = $sessionManager;
    }

    public function execute(string $username, string $password): bool {
        try {
            $credentials = new Credentials($username, $password);
            $user = $this->authService->authenticate($credentials);
            
            $session = UserSession::authenticated($user->getId());
            $this->sessionManager->setUserSession($session);
            
            return true;

        } catch (AuthenticationException $e) {
            error_log('Authentication failed: ' . $e->getMessage());
            return false;
        } catch (\InvalidArgumentException $e) {
            error_log('Invalid credentials format: ' . $e->getMessage());
            return false;
        }
    }
}
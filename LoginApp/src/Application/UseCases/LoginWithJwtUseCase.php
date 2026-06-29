<?php

declare(strict_types=1);

namespace LoginApp\Application\UseCases;

use LoginApp\Domain\Services\LoginService;
use LoginApp\Domain\Services\LoginException;
use LoginApp\Domain\Services\JwtServiceInterface;
use LoginApp\Domain\Services\CsrfTokenServiceInterface;
use LoginApp\Domain\ValueObjects\UserSession;
use LoginApp\Domain\ValueObjects\JwtPayload;
use LoginApp\Domain\ValueObjects\IpAddress;
use LoginApp\Application\Services\SessionManagerInterface;
use LoginApp\Application\Services\CookieManagerInterface;

class LoginWithJwtUseCase {
    
    private LoginService $loginService;
    private JwtServiceInterface $jwtService;
    private CsrfTokenServiceInterface $csrfService;
    private SessionManagerInterface $sessionManager;
    private CookieManagerInterface $cookieManager;
    private int $jwtExpiration;

    public function __construct(
        LoginService $loginService,
        JwtServiceInterface $jwtService,
        CsrfTokenServiceInterface $csrfService,
        SessionManagerInterface $sessionManager,
        CookieManagerInterface $cookieManager,
        int $jwtExpiration = 3600
    ) {
        $this->loginService = $loginService;
        $this->jwtService = $jwtService;
        $this->csrfService = $csrfService;
        $this->sessionManager = $sessionManager;
        $this->cookieManager = $cookieManager;
        $this->jwtExpiration = $jwtExpiration;
    }

    /**
     * Execute login
     * 
     * @return string Status code
     */
    public function execute(string $email, string $password, string $csrfToken): string {
        error_log('=== Login Attempt Start ===');
        error_log('Email: ' . $email);
        error_log('CSRF Token: ' . $csrfToken);
        
        // Validate CSRF token
        if (!$this->csrfService->validateToken($csrfToken)) {
            error_log('CSRF validation failed');
            return '5';
        }
        error_log('CSRF validation passed');

        try {
            // Get IP address
            error_log('Getting IP address...');
            $ipAddress = IpAddress::fromServer();
            error_log('IP Address: ' . $ipAddress->getValue());

            // Authenticate user
            error_log('Authenticating user...');
            $user = $this->loginService->authenticateByEmail($email, $password, $ipAddress);
            error_log('User authenticated: ID=' . $user->getId() . ', Email=' . $user->getEmail());

            // Create session
            error_log('Creating session...');
            $session = UserSession::authenticated($user->getId());
            $this->sessionManager->setUserSession($session);
            error_log('Session created');

            // Create JWT payload
            error_log('Creating JWT payload...');
            $payload = new JwtPayload($user->getId(), $user->getEmail(), $this->jwtExpiration);
            error_log('JWT Payload: ' . json_encode($payload->toArray()));
            
            // Encode JWT
            error_log('Encoding JWT...');
            $token = $this->jwtService->encode($payload->toArray());
            error_log('JWT Token (first 20 chars): ' . substr($token, 0, 20) . '...');

            // Set JWT cookie
            error_log('Setting JWT cookie...');
            $this->cookieManager->set('jwt_token', $token, [
                'expires' => $payload->getExpiresAt(),
                'path' => '/',
                'httponly' => true,
                'secure' => true,
                'samesite' => 'Strict'
            ]);
            error_log('JWT cookie set');

            error_log('=== Login Attempt Success ===');
            return '0'; // Success

        } catch (LoginException $e) {
            error_log('=== Login Exception ===');
            error_log('Code: ' . $e->getCode());
            error_log('Message: ' . $e->getMessage());
            error_log('File: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Trace: ' . $e->getTraceAsString());
            return (string)$e->getCode();
        } catch (\Exception $e) {
            error_log('=== General Exception ===');
            error_log('Code: ' . $e->getCode());
            error_log('Message: ' . $e->getMessage());
            error_log('File: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Trace: ' . $e->getTraceAsString());
            return '2'; // Database error
        }
    }   
}
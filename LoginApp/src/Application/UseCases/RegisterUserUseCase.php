<?php

declare(strict_types=1);

namespace LoginApp\Application\UseCases;

use LoginApp\Domain\Services\RegistrationService;
use LoginApp\Domain\Services\RegistrationException;
use LoginApp\Domain\Services\EmailVerificationService;
use LoginApp\Domain\Services\EmailVerificationException;
use LoginApp\Domain\Services\CsrfTokenServiceInterface;
use LoginApp\Domain\ValueObjects\Name;
use LoginApp\Domain\ValueObjects\Email;
use LoginApp\Domain\ValueObjects\Password;
use LoginApp\Domain\ValueObjects\RegistrationData;
use LoginApp\Infrastructure\Services\EmailService;

class RegisterUserUseCase {
    
    private RegistrationService $registrationService;
    private EmailVerificationService $verificationService;
    private EmailService $emailService;
    private CsrfTokenServiceInterface $csrfService;
    private string $validateEmailEndpoint;

    public function __construct(
        RegistrationService $registrationService,
        EmailVerificationService $verificationService,
        EmailService $emailService,
        CsrfTokenServiceInterface $csrfService,
        string $validateEmailEndpoint
    ) {
        $this->registrationService = $registrationService;
        $this->verificationService = $verificationService;
        $this->emailService = $emailService;
        $this->csrfService = $csrfService;
        $this->validateEmailEndpoint = $validateEmailEndpoint;
    }

    /**
     * Register a new user and send verification email
     * 
     * @return array Array of error codes (empty if successful)
     */
    public function execute(
        string $nameInput,
        string $emailInput,
        string $passwordInput,
        string $confirmPasswordInput,
        string $csrfToken
    ): array {
        $errors = [];

        // Validate CSRF token first
        if (!$this->csrfService->validateToken($csrfToken)) {
            return [9]; // Invalid CSRF token
        }

        // Validate and create value objects
        try {
            $name = new Name($nameInput);
        } catch (\InvalidArgumentException $e) {
            $errors[] = (int)$e->getCode();
        }

        try {
            $email = new Email($emailInput);
        } catch (\InvalidArgumentException $e) {
            $errors[] = (int)$e->getCode();
        }

        try {
            $password = new Password($passwordInput);
            
            if (!$password->matches($confirmPasswordInput)) {
                $errors[] = 5; // Password confirmation mismatch
            }
        } catch (\InvalidArgumentException $e) {
            $errors[] = (int)$e->getCode();
        }

        // If validation failed, return errors
        if (!empty($errors)) {
            return $errors;
        }

        // Register the user
        try {
            $registrationData = new RegistrationData($name, $email, $password);
            $user = $this->registrationService->register($registrationData);

            // Send verification email
            try {
                $verificationData = $this->verificationService->createVerificationRequest($email->getValue());
                
                $verificationLink = $this->validateEmailEndpoint . '/' . 
                                  $verificationData['request_id'] . '/' . 
                                  $verificationData['token']->urlSafeEncode();
                
                $emailBody = '<a href="' . htmlspecialchars($verificationLink) . '">Click this link to verify your email</a>';

                $this->emailService->send(
                    $user->getEmail(),
                    $user->getName(),
                    'Email Verification',
                    $emailBody
                );

                return [0]; // Success

            } catch (EmailVerificationException $e) {
                error_log("Email verification error: " . $e->getMessage());
                return [$e->getCode() + 9];
            } catch (\Exception $e) {
                error_log("Email send error: " . $e->getMessage());
                return [10]; // Failed to send email (1 + 9)
            }

        } catch (RegistrationException $e) {
            error_log("Registration error: " . $e->getMessage());
            return [$e->getCode()];
        }
    }
}
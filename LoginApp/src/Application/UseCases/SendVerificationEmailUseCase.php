<?php
namespace LoginApp\Application\UseCases;

use LoginApp\Domain\Services\EmailVerificationService;
use LoginApp\Domain\Services\EmailVerificationException;
use LoginApp\Infrastructure\Services\EmailService;

class SendVerificationEmailUseCase {
    
    private EmailVerificationService $verificationService;
    private EmailService $emailService;
    private string $validateEmailEndpoint;

    public function __construct(
        EmailVerificationService $verificationService,
        EmailService $emailService,
        string $validateEmailEndpoint
    ) {
        $this->verificationService = $verificationService;
        $this->emailService = $emailService;
        $this->validateEmailEndpoint = $validateEmailEndpoint;
    }

    public function execute(string $email): int {
        try {
            $data = $this->verificationService->createVerificationRequest($email);
            
            $user = $data['user'];
            $requestId = $data['request_id'];
            $token = $data['token'];

            $verificationLink = $this->validateEmailEndpoint . '/' . $requestId . '/' . $token->urlSafeEncode();
            
            $emailBody = '<a href="' . htmlspecialchars($verificationLink) . '">Click this link to verify your email</a>';

            $this->emailService->send(
                $user->getEmail(),
                $user->getName(),
                'Email Verification',
                $emailBody
            );

            return 0; // Success

        } catch (EmailVerificationException $e) {
            error_log("Email verification error: " . $e->getMessage());
            return $e->getCode();
        } catch (\Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return EmailVerificationException::EMAIL_SEND_FAILED;
        }
    }
}
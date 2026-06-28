<?php

declare(strict_types=1); 

namespace LoginApp\Application\Controllers;

use LoginApp\Application\Config\Config;
use LoginApp\Application\UseCases\SendVerificationEmailUseCase;
use LoginApp\Domain\Services\EmailVerificationService;
use LoginApp\Infrastructure\Services\EmailService;
use LoginApp\Infrastructure\Repositories\UserRepository;
use LoginApp\Infrastructure\Repositories\EmailVerificationRequestRepository;

class EmailController {

    public static function sendValidationEmail(string $email): int {
        $config = Config::getInstance();
        $connection = new LoginController();

        // Dependency Injection / Service Container Setup
        $userRepo = new UserRepository($connection);
        $requestRepo = new EmailVerificationRequestRepository($connection);

        $verificationService = new EmailVerificationService(
            $userRepo,
            $requestRepo,
            $config->get('MAX_EMAIL_VERIFICATION_REQUESTS_PER_DAY'),
            $config->get('PASSWORD_DEFAULT')
        );

        $emailService = new EmailService(
            $config->get('SMTP_HOST'),
            $config->get('SMTP_USERNAME'),
            $config->get('SMTP_PASSWORD'),
            $config->get('SMTP_PORT'),
            $config->get('SMTP_FROM'),
            $config->get('SMTP_FROM_NAME')
        );

        $useCase = new SendVerificationEmailUseCase(
            $verificationService,
            $emailService,
            $config->get('VALIDATE_EMAIL_ENDPOINT')
        );

        return $useCase->execute($email);
    }
}
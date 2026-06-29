<?php
namespace LoginApp\Application\Container;

use LoginApp\Application\Config\Config;
use LoginApp\Infrastructure\Database\DatabaseInterface;
use LoginApp\Infrastructure\Database\MySQLDatabase;
use LoginApp\Infrastructure\Repositories\UserRepository;
use LoginApp\Infrastructure\Repositories\EmailVerificationRequestRepository;
use LoginApp\Infrastructure\Repositories\LoginAttemptRepository;
use LoginApp\Domain\Repositories\UserRepositoryInterface;
use LoginApp\Domain\Repositories\EmailVerificationRequestRepositoryInterface;
use LoginApp\Domain\Repositories\LoginAttemptRepositoryInterface;
use LoginApp\Domain\Services\AuthenticationService;
use LoginApp\Domain\Services\EmailVerificationService;
use LoginApp\Domain\Services\RegistrationService;
use LoginApp\Domain\Services\LoginService;
use LoginApp\Domain\Services\CsrfTokenServiceInterface;
use LoginApp\Domain\Services\JwtServiceInterface;
use LoginApp\Infrastructure\Services\EmailService;
use LoginApp\Infrastructure\Services\PhpSessionManager;
use LoginApp\Infrastructure\Services\PhpCookieManager;
use LoginApp\Infrastructure\Services\CsrfTokenService;
use LoginApp\Infrastructure\Services\FirebaseJwtService;
use LoginApp\Application\Services\SessionManagerInterface;
use LoginApp\Application\Services\CookieManagerInterface;
use LoginApp\Application\UseCases\LoginUseCase;
use LoginApp\Application\UseCases\LoginWithJwtUseCase;
use LoginApp\Application\UseCases\CheckAuthenticationUseCase;
use LoginApp\Application\UseCases\LogoutUseCase;
use LoginApp\Application\UseCases\SendVerificationEmailUseCase;
use LoginApp\Application\UseCases\RegisterUserUseCase;

class ServiceContainer {
    
    private static ?ServiceContainer $instance = null;
    private array $services = [];
    private Config $config;

    private function __construct() {
        $this->config = Config::getInstance();
        $this->registerServices();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerServices(): void {
        // Database
        $this->services[DatabaseInterface::class] = function() {
            error_log('Creating MySQLDatabase instance');
            return new MySQLDatabase();
        };

        // Session
        $this->services[SessionManagerInterface::class] = function() {
            return new PhpSessionManager();
        };

        // Cookie
        $this->services[CookieManagerInterface::class] = function() {
            return new PhpCookieManager();
        };

        // CSRF
        $this->services[CsrfTokenServiceInterface::class] = function() {
            return new CsrfTokenService(
                $this->get(SessionManagerInterface::class)
            );
        };

        // JWT
        $this->services[JwtServiceInterface::class] = function() {
            return new FirebaseJwtService(
                $this->config->get('JWT_SECRET'),
                'HS256'
            );
        };

        // Repositories
        $this->services[UserRepositoryInterface::class] = function() {
            return new UserRepository($this->get(DatabaseInterface::class));
        };

        $this->services[EmailVerificationRequestRepositoryInterface::class] = function() {
            return new EmailVerificationRequestRepository($this->get(DatabaseInterface::class));
        };

        $this->services[LoginAttemptRepositoryInterface::class] = function() {
            return new LoginAttemptRepository($this->get(DatabaseInterface::class));
        };

        // Domain Services
        $this->services[AuthenticationService::class] = function() {
            return new AuthenticationService(
                $this->get(UserRepositoryInterface::class)
            );
        };

        $this->services[RegistrationService::class] = function() {
            return new RegistrationService(
                $this->get(UserRepositoryInterface::class)
            );
        };

        $this->services[LoginService::class] = function() {
            return new LoginService(
                $this->get(UserRepositoryInterface::class),
                $this->get(LoginAttemptRepositoryInterface::class),
                $this->config->get('MAX_LOGIN_ATTEMPTS_PER_HOUR'),
                3600 // 1 hour window
            );
        };

        $this->services[EmailVerificationService::class] = function() {
            return new EmailVerificationService(
                $this->get(UserRepositoryInterface::class),
                $this->get(EmailVerificationRequestRepositoryInterface::class),
                $this->config->get('MAX_EMAIL_VERIFICATION_REQUESTS_PER_DAY'),
                $this->config->get('PASSWORD_DEFAULT')
            );
        };

        // Infrastructure Services
        $this->services[EmailService::class] = function() {
            return new EmailService(
                $this->config->get('SMTP_HOST'),
                $this->config->get('SMTP_USERNAME'),
                $this->config->get('SMTP_PASSWORD'),
                $this->config->get('SMTP_PORT'),
                $this->config->get('SMTP_FROM'),
                $this->config->get('SMTP_FROM_NAME')
            );
        };

        // Use Cases
        $this->services[LoginUseCase::class] = function() {
            return new LoginUseCase(
                $this->get(AuthenticationService::class),
                $this->get(SessionManagerInterface::class)
            );
        };

        $this->services[LoginWithJwtUseCase::class] = function() {
                return new LoginWithJwtUseCase(
                $this->get(LoginService::class),
                $this->get(JwtServiceInterface::class),
                $this->get(CsrfTokenServiceInterface::class),
                $this->get(SessionManagerInterface::class),
                $this->get(CookieManagerInterface::class),
                $this->config->get('JWT_EXPIRATION')
            );
        };

        $this->services[CheckAuthenticationUseCase::class] = function() {
            return new CheckAuthenticationUseCase(
                $this->get(SessionManagerInterface::class)
            );
        };

        $this->services[LogoutUseCase::class] = function() {
            return new LogoutUseCase(
                $this->get(SessionManagerInterface::class),
                $this->get(CookieManagerInterface::class),
                $this->get(CsrfTokenServiceInterface::class)
            );
        };

        $this->services[SendVerificationEmailUseCase::class] = function() {
            return new SendVerificationEmailUseCase(
                $this->get(EmailVerificationService::class),
                $this->get(EmailService::class),
                $this->config->get('VALIDATE_EMAIL_ENDPOINT')
            );
        };

        $this->services[RegisterUserUseCase::class] = function() {
            return new RegisterUserUseCase(
                $this->get(RegistrationService::class),
                $this->get(EmailVerificationService::class),
                $this->get(EmailService::class),
                $this->get(CsrfTokenServiceInterface::class),
                $this->config->get('VALIDATE_EMAIL_ENDPOINT')
            );
        };
    }

    public function get(string $serviceClass): object {
        if (!isset($this->services[$serviceClass])) {
            throw new \RuntimeException("Service {$serviceClass} not found in container");
        }

        // Lazy loading
        if (is_callable($this->services[$serviceClass])) {
            $this->services[$serviceClass] = $this->services[$serviceClass]();
        }

        return $this->services[$serviceClass];
    }
}
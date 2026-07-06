<?php


namespace MainApp\Application\Container;

use MainApp\Application\Config\Config;
use MainApp\Application\Services\AuthenticationService;
use MainApp\Application\Services\CookieManagerInterface;
use MainApp\Application\Services\PasswordEncryptionServiceInterface;
use MainApp\Application\Services\SessionManagerInterface;
use MainApp\Application\UseCases\CheckAuthenticationUseCase;
use MainApp\Application\UseCases\CreateImapAccountUseCase;
use MainApp\Application\UseCases\DeleteImapAccountUseCase;
use MainApp\Application\UseCases\GetUserImapAccountsUseCase;
use MainApp\Application\UseCases\GetUserImapAccountUseCase;
use MainApp\Application\UseCases\LogoutUseCase;
use MainApp\Application\UseCases\UpdateImapAccountUseCase;
use MainApp\Domain\Repositories\ImapAccountRepositoryInterface;
use MainApp\Domain\Repositories\UserRepositoryInterface;
use MainApp\Domain\Services\CsrfTokenServiceInterface;
use MainApp\Infrastructure\Database\DatabaseInterface;
use MainApp\Infrastructure\Database\MySQLDatabase;
use MainApp\Infrastructure\Repositories\ImapAccountRepository;
use MainApp\Infrastructure\Services\AesPasswordEncryptionService;
use MainApp\Infrastructure\Services\CsrfTokenService;
use MainApp\Infrastructure\Services\PhpCookieManager;
use MainApp\Infrastructure\Services\PhpSessionManager;


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

        // Domain Services
        $this->services[AuthenticationService::class] = function() {
            return new AuthenticationService(
                $this->get(UserRepositoryInterface::class)
            );
        };

        $this->services[DatabaseInterface::class] = function() {
            return new MySQLDatabase();
        };

        $this->services[LogoutUseCase::class] = function() {
            return new LogoutUseCase(
                $this->get(SessionManagerInterface::class),
                $this->get(CookieManagerInterface::class),
                $this->get(CsrfTokenServiceInterface::class)
            );
        };
        $this->services[CheckAuthenticationUseCase::class] = function() {
            return new CheckAuthenticationUseCase(
                $this->get(SessionManagerInterface::class)
            );
        };

        // IMAP Account Repository
        $this->services[ImapAccountRepositoryInterface::class] = function() {
            return new ImapAccountRepository(
                $this->get(DatabaseInterface::class)
            );
        };

        // IMAP Account Use Cases
        $this->services[GetUserImapAccountsUseCase::class] = function() {
            return new GetUserImapAccountsUseCase(
                $this->get(ImapAccountRepositoryInterface::class)
            );
        };

        $this->services[PasswordEncryptionServiceInterface::class] = function() {
            return new AesPasswordEncryptionService(
                $this->config->get('ENCRYPTION_KEY') ?? 'your-32-character-secret-key-here-change-me!'
            );
        };

        $this->services[DeleteImapAccountUseCase::class] = function() {
            return new DeleteImapAccountUseCase(
                $this->get(ImapAccountRepositoryInterface::class)
            );
        };

        $this->services[GetUserImapAccountUseCase::class] = function() {
            return new GetUserImapAccountUseCase(
                $this->get(ImapAccountRepositoryInterface::class)
            );
        };

        $this->services[CreateImapAccountUseCase::class] = function() {
            return new CreateImapAccountUseCase(
                $this->get(ImapAccountRepositoryInterface::class),
                $this->get(PasswordEncryptionServiceInterface::class)   
            );
        };

        $this->services[GetUserImapAccountsUseCase::class] = function() {
            return new GetUserImapAccountsUseCase(
                $this->get(ImapAccountRepositoryInterface::class)
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
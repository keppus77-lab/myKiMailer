<?php


namespace MainApp\Application\Container;

use MainApp\Application\Services\CookieManagerInterface;
use MainApp\Application\Services\SessionManagerInterface;
use MainApp\Application\UseCases\LogoutUseCase;
use MainApp\Application\UseCases\CheckAuthenticationUseCase;

use MainApp\Domain\Repositories\UserRepositoryInterface;
use MainApp\Infrastructure\Services\AuthenticationService;
use MainApp\Domain\Services\CsrfTokenServiceInterface;
use MainApp\Infrastructure\Services\CsrfTokenService;
use MainApp\Infrastructure\Services\PhpCookieManager;

use MainApp\Infrastructure\Services\PhpSessionManager;
use MainApp\Application\Config\Config;

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
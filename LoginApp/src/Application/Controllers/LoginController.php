<?php
namespace LoginApp\Application\Controllers;

use LoginApp\Application\Container\ServiceContainer;
use LoginApp\Application\UseCases\LoginUseCase;
use LoginApp\Application\UseCases\CheckAuthenticationUseCase;
use LoginApp\Application\UseCases\LogoutUseCase;

class LoginController {
    
    private ServiceContainer $container;

    public function __construct() {
        $this->container = ServiceContainer::getInstance();
    }

    public static function isLoggedIn(): bool {
        $container = ServiceContainer::getInstance();
        $useCase = $container->get(CheckAuthenticationUseCase::class);
        return $useCase->execute();
    }

    public static function login(string $username, string $password): bool {
        $container = ServiceContainer::getInstance();
        $useCase = $container->get(LoginUseCase::class);
        return $useCase->execute($username, $password);
    }

    public static function logout(): void {
        $container = ServiceContainer::getInstance();
        $useCase = $container->get(LogoutUseCase::class);
        $useCase->execute();
    }

    public static function getCurrentUserId(): ?int {
        $container = ServiceContainer::getInstance();
        $useCase = $container->get(CheckAuthenticationUseCase::class);
        return $useCase->getCurrentUserId();
    }
}
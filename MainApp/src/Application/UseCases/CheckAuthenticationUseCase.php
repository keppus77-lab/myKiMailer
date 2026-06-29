<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Application\Services\SessionManagerInterface;

class CheckAuthenticationUseCase {
    
    private SessionManagerInterface $sessionManager;

    public function __construct(SessionManagerInterface $sessionManager) {
        $this->sessionManager = $sessionManager;
    }

    public function execute(): bool {
        $session = $this->sessionManager->getCurrentSession();
        return $session->isAuthenticated();
    }

    public function getCurrentUserId(): ?int {
        $session = $this->sessionManager->getCurrentSession();
        return $session->getUserId();
    }
}
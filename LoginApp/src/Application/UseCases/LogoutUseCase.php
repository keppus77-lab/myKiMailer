<?php

declare(strict_types=1);

namespace LoginApp\Application\UseCases;

use LoginApp\Application\Services\SessionManagerInterface;

class LogoutUseCase {
    
    private SessionManagerInterface $sessionManager;

    public function __construct(SessionManagerInterface $sessionManager) {
        $this->sessionManager = $sessionManager;
    }

    public function execute(): void {
        $this->sessionManager->clear();
        $this->sessionManager->destroy();
    }
}
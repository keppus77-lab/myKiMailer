<?php

declare(strict_types=1);

namespace MainApp\Application\Services;

use MainApp\Domain\ValueObjects\UserSession;

interface SessionManagerInterface {
    public function start(): void;
    public function destroy(): void;
    public function clear(): void;
    public function get(string $key): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function getCurrentSession(): UserSession;
    public function setUserSession(UserSession $session): void;
}
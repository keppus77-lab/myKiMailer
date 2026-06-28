<?php

declare(strict_types=1);

namespace LoginApp\Application\Services;

use LoginApp\Domain\ValueObjects\UserSession;

interface SessionManagerInterface {
    public function start(): void;
    public function destroy(): void;
    public function get(string $key): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function getCurrentSession(): UserSession;
    public function setUserSession(UserSession $session): void;
}
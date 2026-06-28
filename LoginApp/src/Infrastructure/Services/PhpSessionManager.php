<?php

declare(strict_types=1);

namespace LoginApp\Infrastructure\Services;

use LoginApp\Application\Services\SessionManagerInterface;
use LoginApp\Domain\ValueObjects\UserSession;

class PhpSessionManager implements SessionManagerInterface {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function destroy(): void {
        session_destroy();
    }

    public function get(string $key): mixed {
        return $_SESSION[$key] ?? null;
    }

    public function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public function getCurrentSession(): UserSession {
        if ($this->has('loggedin') && $this->get('loggedin') === true) {
            return UserSession::authenticated((int)$this->get('userid'));
        }
        
        return UserSession::guest();
    }

    public function setUserSession(UserSession $session): void {
        $data = $session->toArray();
        $this->set('loggedin', $data['loggedin']);
        $this->set('userid', $data['userid']);
    }

    public function clear(): void {
        session_unset();
    }
}
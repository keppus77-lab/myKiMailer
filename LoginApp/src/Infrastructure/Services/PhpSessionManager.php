<?php

declare(strict_types=1);

namespace LoginApp\Infrastructure\Services;

use LoginApp\Application\Services\SessionManagerInterface;
use LoginApp\Domain\ValueObjects\UserSession;

class PhpSessionManager implements SessionManagerInterface {
    
    public function __construct() {
        // DEBUG
        error_log('=== PhpSessionManager Constructor ===');
        error_log('Session status before: ' . session_status());
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            error_log('Session started');
        } else {
            error_log('Session already active');
        }
        
        error_log('Session ID: ' . session_id());
        error_log('Session data: ' . print_r($_SESSION, true));
        error_log('=== End Constructor ===');
    }

    public function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function destroy(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function get(string $key): mixed {
        $value = $_SESSION[$key] ?? null;
        
        // DEBUG
        error_log("SessionManager::get('{$key}'): " . var_export($value, true));
        
        return $value;
    }

    public function set(string $key, mixed $value): void {
        // DEBUG
        error_log("SessionManager::set('{$key}', " . var_export($value, true) . ")");
        
        $_SESSION[$key] = $value;
        
        error_log("After set - Session data: " . print_r($_SESSION, true));
    }

    public function has(string $key): bool {
        $has = isset($_SESSION[$key]);
        
        // DEBUG
        error_log("SessionManager::has('{$key}'): " . var_export($has, true));
        
        return $has;
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
        error_log('=== Session Clear ===');
        error_log('Before clear: ' . print_r($_SESSION, true));
        session_unset();
        error_log('After clear: ' . print_r($_SESSION, true));
    }

    public function regenerate(bool $deleteOldSession = true): bool {
        return session_regenerate_id($deleteOldSession);
    }
}
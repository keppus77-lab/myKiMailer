<?php

declare(strict_types=1);

namespace LoginApp\Infrastructure\Services;

use LoginApp\Application\Services\CookieManagerInterface;

class PhpCookieManager implements CookieManagerInterface {
    
    private array $defaultOptions = [
        'expires' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ];

    public function set(string $name, string $value, array $options = []): void {
        $options = array_merge($this->defaultOptions, $options);
        
        setcookie(
            $name,
            $value,
            $options
        );
    }

    public function get(string $name): ?string {
        return $_COOKIE[$name] ?? null;
    }

    public function delete(string $name, array $options = []): void {
        $options = array_merge($this->defaultOptions, $options);
        $options['expires'] = time() - 3600;
        
        setcookie(
            $name,
            '',
            $options
        );
        
        // Also remove from current script execution
        if (isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
        }
    }

    public function has(string $name): bool {
        return isset($_COOKIE[$name]);
    }
}
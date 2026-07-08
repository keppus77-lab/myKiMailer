<?php
namespace MainApp\Application\DTOs;

class UpdateImapAccountRequest {
    
    public ?string $email;
    public ?string $host;
    public ?int $port;
    public ?string $password; // Optional - nur wenn geändert werden soll
    public ?string $encryption;
    public ?bool $active;
    public ?string $csrfToken;
    
    private array $errors = [];

    public function __construct(array $putData) {
        $this->email = $putData['email'] ?? null;
        $this->host = $putData['host'] ?? null;
        $this->port = isset($putData['port']) ? (int)$putData['port'] : null;
        $this->password = $putData['password'] ?? null; // Kann leer sein
        $this->encryption = $putData['encryption'] ?? null;
        $this->active = isset($putData['active']) ? (bool)$putData['active'] : null;
        $this->csrfToken = $putData['csrf_token'] ?? null;
    }

    /**
     * Validate all fields
     */
    public function isValid(): bool {
        $this->errors = [];

        // Email validation
        if ($this->email !== null) {
            if (empty($this->email)) {
                $this->errors['email'] = 'Email cannot be empty';
            } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $this->errors['email'] = 'Invalid email format';
            } elseif (strlen($this->email) > 255) {
                $this->errors['email'] = 'Email is too long (max 255 characters)';
            }
        }

        // Host validation
        if ($this->host !== null) {
            if (empty($this->host)) {
                $this->errors['host'] = 'IMAP host cannot be empty';
            } elseif (strlen($this->host) > 255) {
                $this->errors['host'] = 'Host is too long (max 255 characters)';
            } elseif (!$this->isValidHostname($this->host)) {
                $this->errors['host'] = 'Invalid hostname format';
            }
        }

        // Port validation
        if ($this->port !== null) {
            if ($this->port < 1 || $this->port > 65535) {
                $this->errors['port'] = 'Port must be between 1 and 65535';
            }
        }

        // Password validation (optional)
        if ($this->password !== null && !empty($this->password)) {
            if (strlen($this->password) > 500) {
                $this->errors['password'] = 'Password is too long';
            }
        }

        // Encryption validation
        if ($this->encryption !== null) {
            if (!in_array($this->encryption, ['ssl', 'tls', 'none'])) {
                $this->errors['encryption'] = 'Invalid encryption type. Must be ssl, tls, or none';
            }
        }

        // CSRF token validation
        if (empty($this->csrfToken)) {
            $this->errors['csrf_token'] = 'CSRF token is required';
        }

        return empty($this->errors);
    }

    private function isValidHostname(string $hostname): bool {
        return filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            || filter_var($hostname, FILTER_VALIDATE_IP) !== false;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function hasError(string $field): bool {
        return isset($this->errors[$field]);
    }

    public function getError(string $field): ?string {
        return $this->errors[$field] ?? null;
    }

    /**
     * Check if password is being updated
     */
    public function hasNewPassword(): bool {
        return $this->password !== null && !empty($this->password);
    }
}
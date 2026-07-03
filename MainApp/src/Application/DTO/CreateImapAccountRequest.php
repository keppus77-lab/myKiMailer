<?php

declare(strict_types=1);

namespace MainApp\Application\DTO;

class CreateImapAccountRequest {
    
    public ?string $email;
    public ?string $host;
    public ?int $port;
    public ?string $password;
    public ?string $encryption;
    public ?string $csrfToken;
    public ?bool $testConnection;
    
    private array $errors = [];

    public function __construct(array $postData) {
        $this->email = $postData['email'] ?? null;
        $this->host = $postData['host'] ?? null;
        $this->port = isset($postData['port']) ? (int)$postData['port'] : null;
        $this->password = $postData['password'] ?? null;
        $this->encryption = $postData['encryption'] ?? 'ssl';
        $this->csrfToken = $postData['csrf_token'] ?? null;
        $this->testConnection = isset($postData['test_connection']) ? (bool)$postData['test_connection'] : false;
    }

    public function isValid(): bool {
        $this->errors = [];

        // Email validation
        if (empty($this->email)) {
            $this->errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Invalid email format';
        } elseif (strlen($this->email) > 255) {
            $this->errors['email'] = 'Email is too long (max 255 characters)';
        }

        // Host validation
        if (empty($this->host)) {
            $this->errors['host'] = 'IMAP host is required';
        } elseif (strlen($this->host) > 255) {
            $this->errors['host'] = 'Host is too long (max 255 characters)';
        } elseif (!$this->isValidHostname($this->host)) {
            $this->errors['host'] = 'Invalid hostname format';
        }

        // Port validation
        if ($this->port === null) {
            $this->errors['port'] = 'Port is required';
        } elseif ($this->port < 1 || $this->port > 65535) {
            $this->errors['port'] = 'Port must be between 1 and 65535';
        }

        // Password validation
        if (empty($this->password)) {
            $this->errors['password'] = 'Password is required';
        } elseif (strlen($this->password) > 500) {
            $this->errors['password'] = 'Password is too long';
        }

        // Encryption validation
        if (!in_array($this->encryption, ['ssl', 'tls', 'none'])) {
            $this->errors['encryption'] = 'Invalid encryption type. Must be ssl, tls, or none';
        }

        // CSRF token validation
        if (empty($this->csrfToken)) {
            $this->errors['csrf_token'] = 'CSRF token is required';
        }

        return empty($this->errors);
    }

    /**
     * Validate hostname format
     */
    private function isValidHostname(string $hostname): bool {
        // Check for valid hostname or IP address
        return filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            || filter_var($hostname, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Test IMAP connection (optional)
     */
    public function testImapConnection(): array {
        if (!$this->isValid()) {
            return [
                'success' => false,
                'error' => 'Invalid credentials data'
            ];
        }

        try {
            // Build connection string
            $connectionString = $this->buildConnectionString();
            
            // Try to connect
            $imap = @imap_open($connectionString, $this->email, $this->password);
            
            if ($imap === false) {
                $error = imap_last_error();
                return [
                    'success' => false,
                    'error' => $error ?: 'Connection failed'
                ];
            }

            // Get mailbox info
            $check = imap_check($imap);
            imap_close($imap);

            return [
                'success' => true,
                'mailbox_info' => [
                    'messages' => $check->Nmsgs ?? 0,
                    'recent' => $check->Recent ?? 0
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build IMAP connection string
     */
    private function buildConnectionString(): string {
        $flags = [];
        
        if ($this->encryption === 'ssl') {
            $flags[] = 'ssl';
        } elseif ($this->encryption === 'tls') {
            $flags[] = 'tls';
        } elseif ($this->encryption === 'none') {
            $flags[] = 'novalidate-cert';
        }

        $flagString = !empty($flags) ? '/' . implode('/', $flags) : '';
        
        return sprintf(
            '{%s:%d/imap%s}INBOX',
            $this->host,
            $this->port,
            $flagString
        );
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

    public static function getCommonProviders(): array {
        return [
            'gmail' => [
                'name' => 'Gmail',
                'host' => 'imap.gmail.com',
                'port' => 993,
                'encryption' => 'ssl',
                'note' => 'Requires App Password if 2FA is enabled'
            ],
            'outlook' => [
                'name' => 'Outlook / Office 365',
                'host' => 'outlook.office365.com',
                'port' => 993,
                'encryption' => 'ssl',
                'note' => null
            ],
            'yahoo' => [
                'name' => 'Yahoo Mail',
                'host' => 'imap.mail.yahoo.com',
                'port' => 993,
                'encryption' => 'ssl',
                'note' => 'Requires App Password'
            ],
            'icloud' => [
                'name' => 'iCloud Mail',
                'host' => 'imap.mail.me.com',
                'port' => 993,
                'encryption' => 'ssl',
                'note' => 'Requires App-Specific Password'
            ],
            'gmx' => [
                'name' => 'GMX',
                'host' => 'imap.gmx.net',
                'port' => 993,
                'encryption' => 'ssl',
                'note' => null
            ],
            'web.de' => [
                'name' => 'WEB.DE',
                'host' => 'imap.web.de',
                'port' => 993,
                'encryption' => 'ssl',
                'note' => null
            ],
            '1und1' => [
                'name' => '1&1',
                'host' => 'imap.1und1.de',
                'port' => 993,
                'encryption' => 'ssl',
                'note' => null
            ]
        ];
    }

    public function autoDetectProvider(): ?array {
        if (!$this->email) {
            return null;
        }

        $domain = strtolower(substr($this->email, strpos($this->email, '@') + 1));
        
        $providers = self::getCommonProviders();
        
        // Exact match
        foreach ($providers as $key => $config) {
            if ($domain === $key || strpos($domain, $key) !== false) {
                return $config;
            }
        }

        // Partial match
        foreach ($providers as $key => $config) {
            if (stripos($domain, str_replace('.', '', $key)) !== false) {
                return $config;
            }
        }

        return null;
    }

    /**
     * Apply auto-detected provider settings
     */
    public function applyProviderSettings(array $settings): void {
        if (isset($settings['host']) && empty($this->host)) {
            $this->host = $settings['host'];
        }
        if (isset($settings['port']) && $this->port === null) {
            $this->port = $settings['port'];
        }
        if (isset($settings['encryption']) && empty($this->encryption)) {
            $this->encryption = $settings['encryption'];
        }
    }
}
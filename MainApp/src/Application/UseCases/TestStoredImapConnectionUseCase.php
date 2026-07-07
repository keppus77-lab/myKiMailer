<?php
namespace MainApp\Application\UseCases;

use MainApp\Domain\Repositories\ImapAccountRepositoryInterface;
use MainApp\Application\Services\PasswordEncryptionServiceInterface;

class TestStoredImapConnectionUseCase {
    
    private ImapAccountRepositoryInterface $imapAccountRepository;
    private PasswordEncryptionServiceInterface $encryptionService;
    private int $timeout = 10; // Seconds

    public function __construct(
        ImapAccountRepositoryInterface $imapAccountRepository,
        PasswordEncryptionServiceInterface $encryptionService,
        int $timeout = 10
    ) {
        $this->imapAccountRepository = $imapAccountRepository;
        $this->encryptionService = $encryptionService;
        $this->timeout = $timeout;
    }

    public function execute(int $accountId, int $userId): array {
        
        $startTime = microtime(true);
        
        // Get account
        $account = $this->imapAccountRepository->findByIdAndUserId($accountId, $userId);
        
        if (!$account) {
            throw new \RuntimeException('IMAP account not found or access denied');
        }

        // Decrypt password
        try {
            $plainPassword = $this->encryptionService->decrypt($account->getEncryptedPassword());
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to decrypt account password');
        }

        // Test connection
        $result = $this->testConnection(
            $account->getEmail(),
            $account->getHost(),
            $account->getPort(),
            $plainPassword,
            $account->getEncryption()
        );

        // Add timing info
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $result['duration_ms'] = $duration;

        // Update last_checked timestamp if successful
        if ($result['success']) {
            $this->imapAccountRepository->updateLastChecked($accountId);
        }

        return $result;
    }

    private function testConnection(
        string $email,
        string $host,
        int $port,
        string $password,
        string $encryption
    ): array {
        
        try {
            // Set timeout
            imap_timeout(IMAP_OPENTIMEOUT, $this->timeout);
            
            // Build connection string
            $connectionString = $this->buildConnectionString($host, $port, $encryption);
            
            // Try to connect
            set_error_handler(function() {});
            $imap = @imap_open($connectionString, $email, $password, OP_HALFOPEN);
            restore_error_handler();
            
            if ($imap === false) {
                $error = imap_last_error();
                $alerts = imap_alerts();
                $errors = imap_errors();
                
                return [
                    'success' => false,
                    'error' => $error ?: 'Connection failed',
                    'alerts' => $alerts ?: [],
                    'errors' => $errors ?: [],
                    'connection_details' => [
                        'host' => $host,
                        'port' => $port,
                        'encryption' => $encryption,
                        'email' => $email
                    ],
                    'troubleshooting' => $this->getTroubleshootingTips($error)
                ];
            }

            // Connection successful
            $check = imap_check($imap);
            $mailboxes = imap_list($imap, $connectionString, '*');
            $quota = @imap_get_quotaroot($imap, 'INBOX');
            
            imap_close($imap);

            return [
                'success' => true,
                'message' => 'Connection successful',
                'mailbox_info' => [
                    'total_messages' => $check->Nmsgs ?? 0,
                    'recent_messages' => $check->Recent ?? 0,
                    'unread_messages' => $check->Nmsgs - $check->Recent ?? 0,
                    'mailbox_count' => count($mailboxes ?? []),
                    'mailboxes' => array_map(function($mb) use ($connectionString) {
                        return str_replace($connectionString, '', $mb);
                    }, $mailboxes ?? []),
                    'last_check' => $check->Date ?? null,
                    'quota' => $quota ?? null
                ],
                'connection_details' => [
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption,
                    'email' => $email,
                    'driver' => $check->Driver ?? 'unknown'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
                'connection_details' => [
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption
                ]
            ];
        }
    }

    private function buildConnectionString(string $host, int $port, string $encryption): string {
        $flags = ['imap'];
        
        if ($encryption === 'ssl') {
            $flags[] = 'ssl';
        } elseif ($encryption === 'tls') {
            $flags[] = 'tls';
        }

        // For development
        $flags[] = 'novalidate-cert';
        
        $flagString = '/' . implode('/', $flags);
        
        return sprintf('{%s:%d%s}', $host, $port, $flagString);
    }

    private function getTroubleshootingTips(?string $error): array {
        $tips = [];

        if (!$error) {
            return $tips;
        }

        $errorLower = strtolower($error);

        if (strpos($errorLower, 'certificate') !== false) {
            $tips[] = 'SSL certificate validation failed. Check if the server uses a valid certificate.';
            $tips[] = 'For self-signed certificates, the connection might work but is less secure.';
        }

        if (strpos($errorLower, 'authenticate') !== false || strpos($errorLower, 'login') !== false) {
            $tips[] = 'Authentication failed. Check your email and password.';
            $tips[] = 'Some providers (Gmail, Yahoo) require App Passwords instead of your regular password.';
            $tips[] = 'Make sure IMAP is enabled in your email account settings.';
        }

        if (strpos($errorLower, 'timeout') !== false || strpos($errorLower, 'timed out') !== false) {
            $tips[] = 'Connection timeout. Check if the host and port are correct.';
            $tips[] = 'Make sure your firewall allows outgoing connections to this port.';
        }

        if (strpos($errorLower, 'connection refused') !== false) {
            $tips[] = 'Connection refused. The server might be down or the port is incorrect.';
            $tips[] = 'Common IMAP ports: 993 (SSL), 143 (TLS/plain)';
        }

        return $tips;
    }
}
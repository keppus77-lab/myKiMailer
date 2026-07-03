<?php
namespace MainApp\Application\UseCases;

use MainApp\Domain\Services\PasswordEncryptionServiceInterface;

class TestImapConnectionUseCase {
    
    private PasswordEncryptionServiceInterface $encryptionService;

    public function __construct(PasswordEncryptionServiceInterface $encryptionService) {
        $this->encryptionService = $encryptionService;
    }

    /**
     * Test IMAP connection
     * 
     * @param string $email
     * @param string $host
     * @param int $port
     * @param string $password
     * @param string $encryption
     * @return array
     */
    public function execute(
        string $email,
        string $host,
        int $port,
        string $password,
        string $encryption = 'ssl'
    ): array {
        
        try {
            // Build connection string
            $connectionString = $this->buildConnectionString($host, $port, $encryption);
            
            // Try to connect
            $imap = @imap_open($connectionString, $email, $password);
            
            if ($imap === false) {
                $error = imap_last_error();
                return [
                    'success' => false,
                    'error' => $error ?: 'Connection failed'
                ];
            }

            // Get mailbox info
            $check = imap_check($imap);
            $mailboxes = imap_list($imap, $connectionString, '*');
            
            imap_close($imap);

            return [
                'success' => true,
                'connection_info' => [
                    'messages' => $check->Nmsgs ?? 0,
                    'recent' => $check->Recent ?? 0,
                    'mailboxes' => count($mailboxes ?? [])
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function buildConnectionString(string $host, int $port, string $encryption): string {
        $flags = [];
        
        if ($encryption === 'ssl') {
            $flags[] = 'ssl';
        } elseif ($encryption === 'tls') {
            $flags[] = 'tls';
        } elseif ($encryption === 'none') {
            $flags[] = 'novalidate-cert';
        }

        $flagString = !empty($flags) ? '/' . implode('/', $flags) : '';
        
        return sprintf(
            '{%s:%d/imap%s}INBOX',
            $host,
            $port,
            $flagString
        );
    }
}
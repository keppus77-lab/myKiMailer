<?php
namespace App\Application\Controllers;

use App\Application\Controllers\LoginController;
use Exception;

use App\Application\Config\Config;
use Psr\Log\LoggerInterface;


use function imap_errors;
use function imap_alerts;

/**
 * IMAP Credentials Manager
 * Sichere Verwaltung von IMAP-Zugangsdaten mit AES-256-CBC Verschlüsselung
 */
class ImapCredentialsController {
    
    private const ENCRYPTION_METHOD = 'aes-256-cbc';
    private \mysqli $mysqli;
    private string $encryptionKey;
    private LoggerInterface $logger;
    private LoginController $cl;

    public function __construct(LoginController $cl, LoggerInterface $logger) {
        $this->mysqli = $cl->getConnection();
        $this->logger = $logger;      
    
        
        // Encryption Key aus Umgebungsvariable laden
        $this->encryptionKey = Config::getInstance()->get('IMAP_ENCRYPTION_KEY');
        
        if (!$this->encryptionKey || strlen($this->encryptionKey) < 32) {
            throw new Exception(
                'IMAP_ENCRYPTION_KEY muss gesetzt sein und mindestens 32 Zeichen haben!'
            );
        }
        
        // Key auf exakt 32 Bytes hashen (für AES-256)
        $this->encryptionKey = hash('sha256', $this->encryptionKey, true);
    }
    
    /**
     * Passwort verschlüsseln
     * 
     * @param string $password Klartext-Passwort
     * @return array ['encrypted' => string, 'iv' => string]
     * @throws Exception bei Verschlüsselungsfehler
     */
    public function encryptPassword(string $password): array {
        // Initialization Vector generieren
        $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_METHOD);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        if ($iv === false) {
            throw new Exception('Konnte IV nicht generieren');
        }
        
        // Verschlüsseln
        $encrypted = openssl_encrypt(
            $password,
            self::ENCRYPTION_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            throw new Exception('Verschlüsselung fehlgeschlagen: ' . openssl_error_string());
        }
        
        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => base64_encode($iv)
        ];
    }
    
    /**
     * Passwort entschlüsseln
     * 
     * @param string $encryptedPassword Base64-kodiertes verschlüsseltes Passwort
     * @param string $iv Base64-kodierter Initialization Vector
     * @return string Klartext-Passwort
     * @throws Exception bei Entschlüsselungsfehler
     */
    public function decryptPassword(string $encryptedPassword, string $iv): string {
        $decrypted = openssl_decrypt(
            base64_decode($encryptedPassword),
            self::ENCRYPTION_METHOD,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            base64_decode($iv)
        );
        
        if ($decrypted === false) {
            throw new Exception('Entschlüsselung fehlgeschlagen: ' . openssl_error_string());
        }
        
        return $decrypted;
    }
    
    /**
     * IMAP-Account speichern
     * 
     * @param int $userId User-ID
     * @param string $email E-Mail-Adresse
     * @param string $host IMAP-Server (z.B. imap.gmail.com)
     * @param int $port IMAP-Port (Standard: 993)
     * @param string $username IMAP-Benutzername
     * @param string $password IMAP-Passwort (wird verschlüsselt gespeichert)
     * @param bool $useSsl SSL verwenden (Standard: true)
     * @return int ID des gespeicherten Accounts
     * @throws Exception bei Datenbankfehler
     */
    public function saveAccount(
    int $userId,
    string $email,
    string $host,
    string $password,    
    string $username, 
    bool $useSsl = true,
    int $port = 993,
    
): int {
    // Username = Email wenn nicht angegeben
    
    
    // Prüfen ob Account bereits existiert
    $checkStmt = $this->mysqli->prepare("
        SELECT id 
        FROM imap_accounts 
        WHERE user_id = ? 
        AND email = ? 
        AND imap_host = ? 
        AND imap_username = ?
        AND is_active = 1
    ");
    
    $checkStmt->bind_param("isss", $userId, $email, $host, $username);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        return -1;
        
    }
    $checkStmt->close();
    
    // Passwort verschlüsseln
    $encrypted = $this->encryptPassword($password);
    
    // Account speichern
    $stmt = $this->mysqli->prepare("
        INSERT INTO imap_accounts 
        (user_id, email, imap_host, imap_port, imap_username, 
         imap_password_encrypted, encryption_iv, use_ssl, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    if (!$stmt) {
        throw new Exception('Prepare fehlgeschlagen: ' . $this->mysqli->error);
    }
    
    $useSslInt = $useSsl ? 1 : 0;
    
    // Bind parameters: 8 Werte für INSERT
    $stmt->bind_param(
        "ississsi",
        $userId,                    // i - user_id
        $email,                     // s - email
        $host,                      // s - imap_host
        $port,                      // i - imap_port
        $username,                  // s - imap_username
        $encrypted['encrypted'],    // s - imap_password_encrypted
        $encrypted['iv'],           // s - encryption_iv
        $useSslInt                  // i - use_ssl
    );
    
    // Execute
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Fehler beim Speichern: ' . $error);
    }
    
    // Insert-ID holen
    $insertId = $stmt->insert_id;
    
    // Statement schließen
    $stmt->close();
    
    // Audit-Log
    $this->logAction($userId, 'account_created', $insertId);
    
    return (int) $insertId;
}
    
 
    public function getAccountsByUser( int $userId): ?array
    {
        try {
            $stmt = $this->mysqli->prepare("
                SELECT * FROM imap_accounts 
                WHERE user_id = ? AND is_active = 1
            ");
            
            if (!$stmt) {
                throw new Exception('Prepare fehlgeschlagen: ' . $this->mysqli->error);
            }
            
            $stmt->bind_param('i', $userId);
            
            if (!$stmt->execute()) {
                throw new Exception('Execute fehlgeschlagen: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            
            $accounts = $result->fetch_all(MYSQLI_ASSOC);
          
            $stmt->close();
            
         
            
              
        
            // Type Conversion
            foreach ($accounts as &$account) {
                    $account['imap_password'] = $this->decryptPassword(
                        $account['imap_password_encrypted'],
                        $account['encryption_iv']
                    );
            
                    // Verschlüsselte Daten aus Response entfernen
                    unset($account['imap_password_encrypted']);
                    unset($account['encryption_iv']);
                    
                    // Boolean-Werte konvertieren
                    $account['use_ssl'] = (bool) $account['use_ssl'];
                    $account['is_active'] = (bool) $account['is_active'];
                       
                }
                // Passwort entschlüsseln
                
            
            // Audit-Log
            $this->logAction($userId, 'account_accessed');
            
            return $accounts;
            
        } catch (Exception $e) {
            $this->logger->error('Fehler beim Laden des IMAP-Accounts', [
                'error' => $e->getMessage(),
                
                'user_id' => $userId
            ]);
            throw new Exception('Datenbankfehler: ' . $e->getMessage());
        }
    }

    public function getAccount( int $userId, int $accountId): ?array
    {
        try {
            $stmt = $this->mysqli->prepare("
                SELECT * FROM imap_accounts 
                WHERE user_id = ? AND id = ? AND is_active = 1
            ");
            
            if (!$stmt) {
                throw new Exception('Prepare fehlgeschlagen: ' . $this->mysqli->error);
            }
            
            $stmt->bind_param('ii', $userId, $accountId);
            
            if (!$stmt->execute()) {
                throw new Exception('Execute fehlgeschlagen: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            
            $account = $result->fetch_all(MYSQLI_ASSOC);
            
         
            $stmt->close();
            
         
            // Type Conversion
            
                    $account[0]['imap_password'] = $this->decryptPassword(
                        $account[0]['imap_password_encrypted'],
                        $account[0]['encryption_iv']
                    );
            
                    // Verschlüsselte Daten aus Response entfernen
                    unset($account[0]['imap_password_encrypted']);
                    unset($account[0]['encryption_iv']);
                    
                    // Boolean-Werte konvertieren
                    $account[0]['use_ssl'] = (bool) $account[0]['use_ssl'];
                    $account[0]['is_active'] = (bool) $account[0]['is_active'];
                       
                
                // Passwort entschlüsseln
                
            
            // Audit-Log
            $this->logAction($userId, 'account_accessed');
            
            return $account[0];
            
        } catch (Exception $e) {
            $this->logger->error('Fehler beim Laden des IMAP-Accounts', [
                'error' => $e->getMessage(),
                
                'user_id' => $userId
            ]);
            throw new Exception('Datenbankfehler: ' . $e->getMessage());
        }
    }

    
    /**
     * IMAP-Account aktualisieren
     * 
     * @param int $userId User-ID
          * @param array $data Zu aktualisierende Felder (email, host, port, username, password)
     * @return bool Erfolg
     */
    public function updateAccount(int $userId, int $accountId, array $data): bool 
    {
        $existing = $this->getAccountsByUser($userId);
        if (!$existing) {
            throw new Exception('Account nicht gefunden oder keine Berechtigung');
        }
        
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['email'])) {
            $updates[] = 'email = ?';
            $params[] = $data['email'];
            $types .= 's';
        }
        
        if (isset($data['host'])) {
            $updates[] = 'imap_host = ?';
            $params[] = $data['host'];
            $types .= 's';
        }
        
        if (isset($data['port'])) {
            $updates[] = 'imap_port = ?';
            $params[] = $data['port'];
            $types .= 'i';
        }
        
        if (isset($data['username'])) {
            $updates[] = 'imap_username = ?';
            $params[] = $data['username'];
            $types .= 's';
        }
        
        if (isset($data['use_ssl'])) {
            $updates[] = 'use_ssl = ?';
            $params[] = $data['use_ssl'] ? 1 : 0;
            $types .= 'i';
        }
        
        if (isset($data['password'])) {
            $encrypted = $this->encryptPassword($data['password']);
            $updates[] = 'imap_password_encrypted = ?';
            $updates[] = 'encryption_iv = ?';
            $params[] = $encrypted['encrypted'];
            $params[] = $encrypted['iv'];
            $types .= 'ss';
        }
        
        if (empty($updates)) {
            return true;
        }
        
        $updates[] = 'updated_at = NOW()';
    
        $params[] = $accountId;
        $params[] = $userId;
        $types .= 'ii';
        
        $sql = "UPDATE imap_accounts SET " . implode(', ', $updates) . 
            " WHERE id = ? AND user_id = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Prepare fehlgeschlagen: ' . $this->mysqli->error);
        }
       
        $stmt->execute([$types, ...$params]);
        
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        $this->logAction($userId, 'account_updated');
        
        return $affectedRows > 0;
    }

    /**
     * Helper: Bindet Parameter dynamisch
     */
    private function bindParams(\mysqli_stmt $stmt, string $types, array $params): void
    {
        if (empty($types)) {
            return;
        }
        
        $bindParams = [$types];
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }

    
    /**
     * IMAP-Account löschen (Soft Delete)
     * 
     * @param int $userId User-ID
     * @param int $accountId Account-ID
     * @return bool Erfolg
     */
    public function deleteAccount(int $userId, int $accountId): bool 
    {
        try {
            $stmt = $this->mysqli->prepare("
                UPDATE imap_accounts 
                SET is_active = 0, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            if (!$stmt) {
                throw new Exception('Prepare fehlgeschlagen: ' . $this->mysqli->error);
            }
            
            $stmt->bind_param('ii', $accountId, $userId);
            
            if (!$stmt->execute()) {
                throw new Exception('Execute fehlgeschlagen: ' . $stmt->error);
            }
            
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            if ($affectedRows > 0) {
                // Audit-Log
                $this->logAction($userId, 'account_deleted', $accountId);
                return true;
            }
            
            return false;
            
        } catch (\mysqli_sql_exception $e) {
            $this->logger->error('Fehler beim Löschen des IMAP-Accounts', [
                'error' => $e->getMessage(),
                'account_id' => $accountId,
                'user_id' => $userId
            ]);
            return false;
        }
    }

       
    /**
     * IMAP-Verbindung testen
     * 
     * @param int $userId User-ID
     * @param int $accountId Account-ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(int $userId, int $accountId): array {
        $account = $this->getAccount($userId, $accountId);
        
        if (!$account) {
            return ['success' => false, 'message' => 'Account nicht gefunden'];
        }
        try {
         
            // IMAP-Verbindung aufbauen
            $mailbox = sprintf(
                '{%s:%d/imap%s%s}INBOX',
                $account['imap_host'],
                $account['imap_port'],
                ($account['use_ssl']==1) ? '/ssl' : '',
                '/novalidate-cert' 
            );
          
            imap_timeout(IMAP_OPENTIMEOUT, 15);
            imap_timeout(IMAP_READTIMEOUT, 15);
    
            // Alte Fehler löschen
            @imap_errors();
            @imap_alerts();
            
            // Verbindungsversuch

            $imap = @imap_open(
                $mailbox,
                $account['imap_username'],
                $account['imap_password'],
                0,
                1
            );
                        
            if ($imap === false) {

                $lastError = imap_last_error();
                $errors = imap_errors();
                
                if ($lastError) {
                   echo "   Fehler: $lastError\n";
                }
                
                if ($errors) {
                    echo "   Details: " . implode(', ', $errors) . "\n";
                }
                return [
                    'success' => false, 
                    'message' => 'Verbindung fehlgeschlagen: ' . $lastError. ' - '.implode(', ', $errors)
                ];
            }
            
            // Erfolgreich - Verbindung schließen
            imap_close($imap);
            
            // Audit-Log
            $this->logAction($userId, 'connection_tested', $accountId);
            
            return ['success' => true, 'message' => 'Verbindung erfolgreich'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Fehler: ' . $e->getMessage()];
        }
    }
    
    /**
     * Audit-Log schreiben
     * 
     * @param int $userId User-ID
     * @param string $action Aktion (z.B. 'account_created')
     * @param int|null $accountId Betroffener Account
     * @return void
     */
    private function logAction(int $userId, string $action, ?int $accountId = null): void 
    {
        try {
            $stmt = $this->mysqli->prepare("
                INSERT INTO imap_audit_log 
                (user_id, account_id, action, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            if (!$stmt) {
                throw new Exception('Prepare fehlgeschlagen: ' . $this->mysqli->error);
            }
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Type-String: i=int, i=int (kann NULL sein), s=string, s=string, s=string
            $stmt->bind_param(
                'iisss',
                $userId,
                $accountId,
                $action,
                $ipAddress,
                $userAgent
            );
            
            $stmt->execute();
            $stmt->close();
            
        } catch (Exception $e) {
            // Audit-Log-Fehler nicht nach außen werfen
            error_log('Audit-Log-Fehler: ' . $e->getMessage());
        }
    }

    
    /**
     * Encryption Key generieren (für Setup)
     * 
     * @return string Base64-kodierter Encryption Key
     */
    public static function generateEncryptionKey(): string {
        return base64_encode(random_bytes(32));
    }
}

<?php

declare(strict_types=1);

namespace MainApp\Infrastructure\Services;

use MainApp\Application\Services\PasswordEncryptionServiceInterface;

class AesPasswordEncryptionService implements PasswordEncryptionServiceInterface {
    
    private string $encryptionKey;
    private string $cipher = 'aes-256-cbc';

    public function __construct(string $encryptionKey) {
        if (empty($encryptionKey)) {
            throw new \InvalidArgumentException('Encryption key cannot be empty');
        }
        
        if (strlen($encryptionKey) < 32) {
            throw new \InvalidArgumentException('Encryption key must be at least 32 characters');
        }
        
        $this->encryptionKey = $encryptionKey;
    }

    /**
     * Encrypt a password using AES-256-CBC
     */
    public function encrypt(string $plainPassword): string {
        // Generate initialization vector
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);

        // Encrypt
        $encrypted = openssl_encrypt(
            $plainPassword,
            $this->cipher,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        // Combine IV and encrypted data, then base64 encode
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a password
     */
    public function decrypt(string $encryptedPassword): string {
        // Decode from base64
        $data = base64_decode($encryptedPassword);

        if ($data === false) {
            throw new \RuntimeException('Invalid encrypted data');
        }

        // Extract IV and encrypted data
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        // Decrypt
        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $decrypted;
    }
}
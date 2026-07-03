<?php

declare(strict_types=1);

namespace MainApp\Domain\Services;

interface PasswordEncryptionServiceInterface {
    
    /**
     * Encrypt a password
     * 
     * @param string $plainPassword
     * @return string Encrypted password
     */
    public function encrypt(string $plainPassword): string;
    
    /**
     * Decrypt a password
     * 
     * @param string $encryptedPassword
     * @return string Plain password
     */
    public function decrypt(string $encryptedPassword): string;
}
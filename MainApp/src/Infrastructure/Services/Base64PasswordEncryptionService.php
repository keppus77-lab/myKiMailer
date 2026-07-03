<?php

declare(strict_types=1);

namespace MainApp\Infrastructure\Services;

use MainApp\Domain\Services\PasswordEncryptionServiceInterface;

/**
 * Simple base64 encoding - NOT SECURE!
 * Only use for development/testing
 */
class Base64PasswordEncryptionService implements PasswordEncryptionServiceInterface {
    
    public function encrypt(string $plainPassword): string {
        return base64_encode($plainPassword);
    }

    public function decrypt(string $encryptedPassword): string {
        return base64_decode($encryptedPassword);
    }
}
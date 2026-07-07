<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Domain\Entities\ImapAccount;
use MainApp\Domain\Repositories\ImapAccountRepositoryInterface;
use MainApp\Application\Services\PasswordEncryptionServiceInterface;

class UpdateImapAccountUseCase {
    
    private ImapAccountRepositoryInterface $imapAccountRepository;
    private PasswordEncryptionServiceInterface $encryptionService;

    public function __construct(
        ImapAccountRepositoryInterface $imapAccountRepository,
        PasswordEncryptionServiceInterface $encryptionService
    ) {
        $this->imapAccountRepository = $imapAccountRepository;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Update an IMAP account
     * 
     * @param int $accountId
     * @param int $userId For security - ensure user owns this account
     * @param string $email
     * @param string $host
     * @param int $port
     * @param string|null $password If null, keep existing password
     * @param string $encryption
     * @param bool $active
     * @return ImapAccount
     * @throws \Exception
     */
    public function execute(
        int $accountId,
        int $userId,
        string $email,
        string $host,
        int $port,
        ?string $password,
        string $encryption,
        bool $use_ssl,
        bool $active = true
    ): ImapAccount {
        
        // Get existing account
        $existingAccount = $this->imapAccountRepository->findByIdAndUserId($accountId, $userId);
        
        if (!$existingAccount) {
            throw new \RuntimeException('IMAP account not found or access denied');
        }

        // Encrypt password if provided, otherwise keep existing
        $encryptedPassword = $password !== null 
            ? $this->encryptionService->encrypt($password)
            : $existingAccount->getEncryptedPassword();

        // Create updated entity
        $updatedAccount = new ImapAccount(
            $accountId,
            $userId,
            $email,
            $host,
            $port,
            $encryptedPassword,
            $encryption,
            $use_ssl,
            $active,
            $existingAccount->getCreatedAt(),
            $existingAccount->getLastChecked()
        );

        // Update in repository
        $success = $this->imapAccountRepository->update($updatedAccount);

        if (!$success) {
            throw new \RuntimeException('Failed to update IMAP account');
        }

        return $updatedAccount;
    }
}
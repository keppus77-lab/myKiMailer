<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Domain\Entities\ImapAccount;
use MainApp\Domain\Repositories\ImapAccountRepositoryInterface;
use MainApp\Application\Services\PasswordEncryptionServiceInterface;

class CreateImapAccountUseCase {
    
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
     * Create a new IMAP account for a user
     * 
     * @param int $userId
     * @param string $email
     * @param string $host
     * @param int $port
     * @param string $password Plain password (will be encrypted)
     * @param string $encryption
     * @return ImapAccount
     * @throws \Exception
     */
    public function execute(
        int $userId,
        string $email,
        string $host,
        int $port,
        string $password,
        string $encryption = 'ssl'
    ): ImapAccount {
        
        // Check if account with same email already exists for this user
        $existingAccounts = $this->imapAccountRepository->findByUserId($userId);
        
        foreach ($existingAccounts as $existingAccount) {
            if (strtolower($existingAccount->getEmail()) === strtolower($email)) {
                throw new \RuntimeException('An IMAP account with this email already exists');
            }
        }

        // Encrypt password
        $encryptedPassword = $this->encryptionService->encrypt($password);

        // Create entity
        $account = new ImapAccount(
            0, // ID will be set by database
            $userId,
            $email,
            $host,
            $port,
            $encryptedPassword,
            $encryption,
            true, // active by default
            new \DateTime(), // created_at
            null // last_checked
        );

        // Save to repository
        $accountId = $this->imapAccountRepository->create($account);

        if ($accountId === -1) {
            throw new \RuntimeException('Failed to create IMAP account in database');
        }

        // Return created account with ID
        return new ImapAccount(
            $accountId,
            $userId,
            $email,
            $host,
            $port,
            $encryptedPassword,
            $encryption,
            true,
            new \DateTime(),
            null
        );
    }
}
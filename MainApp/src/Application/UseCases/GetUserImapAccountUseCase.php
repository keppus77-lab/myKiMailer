<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Domain\Repositories\ImapAccountRepositoryInterface;
use MainApp\Domain\Entities\ImapAccount;

class GetUserImapAccountUseCase {
    
    private ImapAccountRepositoryInterface $imapAccountRepository;

    public function __construct(ImapAccountRepositoryInterface $imapAccountRepository) {
        $this->imapAccountRepository = $imapAccountRepository;
    }

    /**
     * Get all IMAP accounts for a user
     * 
     * @param int $userId
     * @return array Array of account data (without passwords)
     */
    public function execute(int $accountId, int $userId): array {
        // Verify ownership
        $account = $this->imapAccountRepository->findByIdAndUserId($accountId, $userId);

        // Convert entities to arrays (without passwords)
        return $account->toArray();
    }
}
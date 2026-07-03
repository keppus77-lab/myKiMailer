<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Domain\Repositories\ImapAccountRepositoryInterface;
use MainApp\Domain\Entities\ImapAccount;

class GetUserImapAccountsUseCase {
    
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
    public function execute(int $userId): array {
        $accounts = $this->imapAccountRepository->findByUserId($userId);

        // Convert entities to arrays (without passwords)
        return array_map(function(ImapAccount $account) {
            return $account->toArray();
        }, $accounts);
    }
}
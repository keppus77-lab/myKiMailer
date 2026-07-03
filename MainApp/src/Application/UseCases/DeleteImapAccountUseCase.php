<?php

declare(strict_types=1);

namespace MainApp\Application\UseCases;

use MainApp\Domain\Repositories\ImapAccountRepositoryInterface;

class DeleteImapAccountUseCase {
    
    private ImapAccountRepositoryInterface $imapAccountRepository;

    public function __construct(ImapAccountRepositoryInterface $imapAccountRepository) {
        $this->imapAccountRepository = $imapAccountRepository;
    }

    /**
     * Delete an IMAP account
     * 
     * @param int $accountId
     * @param int $userId For security - ensure user owns this account
     * @return bool
     */
    public function execute(int $accountId, int $userId): bool {
        // Verify ownership
        $account = $this->imapAccountRepository->findByIdAndUserId($accountId, $userId);
        
        if (!$account) {
            return false; // Account not found or access denied
        }

        // Delete account
        return $this->imapAccountRepository->delete($accountId);
    }
}
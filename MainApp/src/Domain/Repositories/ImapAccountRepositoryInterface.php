<?php

declare(strict_types=1);

namespace MainApp\Domain\Repositories;
          

use MainApp\Domain\Entities\ImapAccount;

interface ImapAccountRepositoryInterface {
    
    /**
     * Find all IMAP accounts for a user
     * 
     * @param int $userId
     * @return array Array of ImapAccount entities
     */
    public function findByUserId(int $userId): array;
    
    /**
     * Find a specific IMAP account by ID
     * 
     * @param int $id
     * @return ImapAccount|null
     */
    public function findById(int $id): ?ImapAccount;
    
    /**
     * Find account by user ID and account ID (for security)
     * 
     * @param int $accountId
     * @param int $userId
     * @return ImapAccount|null
     */
    public function findByIdAndUserId(int $accountId, int $userId): ?ImapAccount;
    
    /**
     * Create new IMAP account
     * 
     * @param ImapAccount $account
     * @return int Created account ID or -1 on failure
     */
    public function create(ImapAccount $account): int;
    
    /**
     * Update IMAP account
     * 
     * @param ImapAccount $account
     * @return bool
     */
    public function update(ImapAccount $account): bool;
    
    /**
     * Delete IMAP account
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
    
    /**
     * Update last checked timestamp
     * 
     * @param int $id
     * @return bool
     */
    public function updateLastChecked(int $id): bool;
}
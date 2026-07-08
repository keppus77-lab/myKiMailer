<?php
namespace LoginApp\Domain\Repositories;

use LoginApp\Domain\Entities\EmailVerificationRequest;
          
interface EmailVerificationRequestRepositoryInterface {
    
    /**
     * Create a new email verification request
     * 
     * @param EmailVerificationRequest $request
     * @return int The ID of the created request, or -1 on failure
     */
    public function create(EmailVerificationRequest $request): int;
    
    /**
     * Count recent verification requests for a user
     * 
     * @param int $userId
     * @param int $sinceTimestamp Unix timestamp to count from
     * @param int $type Request type (default 0 for email verification)
     * @return int Number of requests
     */
    public function countRecentRequestsForUser(int $userId, int $sinceTimestamp, int $type = 0): int;
    
    /**
     * Find a verification request by ID
     * 
     * @param int $id
     * @return EmailVerificationRequest|null
     */
    public function findById(int $id): ?EmailVerificationRequest;
    
    /**
     * Delete a verification request
     * 
     * @param int $id
     * @return bool True on success
     */
    public function delete(int $id): bool;
    
    /**
     * Delete all verification requests for a user
     * 
     * @param int $userId
     * @param int $type Request type (default 0 for email verification)
     * @return bool True on success
     */
    public function deleteAllForUser(int $userId, int $type = 0): bool;
    
    /**
     * Find all expired requests (older than given timestamp)
     * 
     * @param int $expirationTimestamp
     * @return array Array of EmailVerificationRequest entities
     */
    public function findExpired(int $expirationTimestamp): array;
    
    /**
     * Delete all expired requests
     * 
     * @param int $expirationTimestamp
     * @return bool True on success
     */
    public function deleteExpired(int $expirationTimestamp): bool;
}
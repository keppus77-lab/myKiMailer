<?php

declare(strict_types=1);

namespace LoginApp\Domain\Services;

use LoginApp\Domain\Entities\User;
use LoginApp\Domain\Entities\EmailVerificationRequest;
use LoginApp\Domain\ValueObjects\VerificationToken;
use LoginApp\Domain\Repositories\UserRepositoryInterface;
use LoginApp\Domain\Repositories\EmailVerificationRequestRepositoryInterface;

class EmailVerificationService {
    
    private UserRepositoryInterface $userRepository;
    private EmailVerificationRequestRepositoryInterface $requestRepository;
    private int $maxRequestsPerDay;
    private string $passwordAlgorithm;

    public function __construct(
        UserRepositoryInterface $userRepository,
        EmailVerificationRequestRepositoryInterface $requestRepository,
        int $maxRequestsPerDay,
        string $passwordAlgorithm
    ) {
        $this->userRepository = $userRepository;
        $this->requestRepository = $requestRepository;
        $this->maxRequestsPerDay = $maxRequestsPerDay;
        $this->passwordAlgorithm = $passwordAlgorithm;
    }

    /**
     * @throws EmailVerificationException
     */
    public function createVerificationRequest(string $email): array {
        $oneDayAgo = time() - 60 * 60 * 24;
        
        $userData = $this->userRepository->findByEmailWithRequestCount($email, $oneDayAgo);
        
        if (!$userData) {
            throw new EmailVerificationException('User not found', EmailVerificationException::USER_NOT_FOUND);
        }

        $user = $userData['user'];
        $requestCount = $userData['request_count'];

        if ($user->isVerified()) {
            throw new EmailVerificationException('User already verified', EmailVerificationException::ALREADY_VERIFIED);
        }

        if ($requestCount >= $this->maxRequestsPerDay) {
            throw new EmailVerificationException('Too many requests', EmailVerificationException::TOO_MANY_REQUESTS);
        }

        $token = VerificationToken::generate();
        $tokenHash = $token->hash($this->passwordAlgorithm);

        $request = new EmailVerificationRequest(
            null,
            $user->getId(),
            $tokenHash,
            time(),
            0
        );

        $requestId = $this->requestRepository->create($request);

        if ($requestId === -1) {
            throw new EmailVerificationException('Failed to create request', EmailVerificationException::REQUEST_CREATION_FAILED);
        }

        return [
            'user' => $user,
            'request_id' => $requestId,
            'token' => $token
        ];
    }
}
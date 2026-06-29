<?php

declare(strict_types=1);

namespace LoginApp\Domain\Services;

use LoginApp\Domain\Entities\User;
use LoginApp\Domain\ValueObjects\Credentials;
use LoginApp\Domain\ValueObjects\IpAddress;
use LoginApp\Domain\Repositories\UserRepositoryInterface;
use LoginApp\Domain\Repositories\LoginAttemptRepositoryInterface;
use LoginApp\Domain\Exceptions\LoginException;

class LoginService {
    
    private UserRepositoryInterface $userRepository;
    private LoginAttemptRepositoryInterface $attemptRepository;
    private int $maxAttemptsPerHour;
    private int $attemptWindowSeconds;

    public function __construct(
        UserRepositoryInterface $userRepository,
        LoginAttemptRepositoryInterface $attemptRepository,
        int $maxAttemptsPerHour = 5,
        int $attemptWindowSeconds = 3600
    ) {
        $this->userRepository = $userRepository;
        $this->attemptRepository = $attemptRepository;
        $this->maxAttemptsPerHour = $maxAttemptsPerHour;
        $this->attemptWindowSeconds = $attemptWindowSeconds;
    }

    /**
     * Authenticate user with email and password
     * 
     * @throws LoginException
     */
    public function authenticateByEmail(string $email, string $password, IpAddress $ipAddress): User {
        $hourAgo = time() - $this->attemptWindowSeconds;
        
        // Get user with login attempts count
        $userData = $this->userRepository->findByEmailWithLoginAttempts($email, $hourAgo);
        
        if (!$userData) {
            throw new LoginException('Invalid credentials', LoginException::INVALID_CREDENTIALS);
        }

        $user = $userData['user'];
        $attemptCount = $userData['attempt_count'];

        // Check if user is verified
        if (!$user->isVerified()) {
            throw new LoginException('Account not verified', LoginException::NOT_VERIFIED);
        }

        // Check login attempts limit
        if ($attemptCount > $this->maxAttemptsPerHour) {
            throw new LoginException('Too many login attempts', LoginException::TOO_MANY_ATTEMPTS);
        }

        // Verify password
        if (!$user->verifyPassword($password)) {
            // Log failed attempt
            $this->attemptRepository->create(
                $user->getId(),
                $ipAddress->getValue(),
                time()
            );
            
            throw new LoginException('Invalid credentials', LoginException::INVALID_CREDENTIALS);
        }

        // Successful login - clear all login attempts
        $this->attemptRepository->deleteAllForUser($user->getId());

        return $user;
    }

    public function clearLoginAttempts(int $userId): bool {
        return $this->attemptRepository->deleteAllForUser($userId);
    }
}
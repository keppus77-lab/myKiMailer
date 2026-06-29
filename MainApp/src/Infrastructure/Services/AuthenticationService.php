<?php

declare(strict_types=1);

namespace MainApp\Domain\Services;

use MainApp\Domain\Entities\User;
use MainApp\Domain\ValueObjects\Credentials;
use MainApp\Domain\Repositories\UserRepositoryInterface;

class AuthenticationService {
    
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository) {
        $this->userRepository = $userRepository;
    }

    /**
     * Authenticates a user with the given credentials
     * 
     * @throws AuthenticationException
     */
    public function authenticate(Credentials $credentials): User {
        $user = $this->userRepository->findByUsername($credentials->getUsername());

        if (!$user) {
            throw new AuthenticationException(
                'Invalid credentials',
                AuthenticationException::INVALID_CREDENTIALS
            );
        }

        if (!$user->verifyPassword($credentials->getPassword())) {
            throw new AuthenticationException(
                'Invalid credentials',
                AuthenticationException::INVALID_CREDENTIALS
            );
        }

        return $user;
    }

    public function getUserById(int $userId): ?User {
        return $this->userRepository->findById($userId);
    }
}
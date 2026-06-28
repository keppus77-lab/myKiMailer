<?php
namespace LoginApp\Domain\Services;

use LoginApp\Domain\Entities\User;
use LoginApp\Domain\ValueObjects\RegistrationData;
use LoginApp\Domain\Repositories\UserRepositoryInterface;

class RegistrationService {
    
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository) {
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user
     * 
     * @throws RegistrationException
     */
    public function register(RegistrationData $data): User {
        // Check if email is already in use
        $existingUser = $this->userRepository->findByEmail($data->getEmail()->getValue());
        
        if ($existingUser !== null) {
            throw new RegistrationException('Email already in use', RegistrationException::EMAIL_ALREADY_EXISTS);
        }

        // Create the user
        $userId = $this->userRepository->create(
            $data->getName()->getValue(),
            $data->getEmail()->getValue(),
            $data->getPassword()->hash()
        );

        if ($userId === -1) {
            throw new RegistrationException('Failed to create user', RegistrationException::DATABASE_ERROR);
        }

        // Return the created user
        return new User(
            $userId,
            '', // username not set during registration
            $data->getPassword()->hash(),
            $data->getName()->getValue(),
            $data->getEmail()->getValue(),
            false // not verified yet
        );
    }
}
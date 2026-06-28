<?php
namespace LoginApp\Domain\Entities;

class User {
    private int $id;
    private string $username;
    private string $passwordHash;
    private string $name;
    private string $email;
    private bool $verified;

    public function __construct(
        int $id, 
        string $username, 
        string $passwordHash,
        string $name = '',
        string $email = '',
        bool $verified = false
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->name = $name;
        $this->email = $email;
        $this->verified = $verified;
    }

    public function getId(): int { 
        return $this->id; 
    }
    
    public function getUsername(): string { 
        return $this->username; 
    }
    
    public function getPasswordHash(): string { 
        return $this->passwordHash; 
    }
    
    public function getName(): string { 
        return $this->name; 
    }
    
    public function getEmail(): string { 
        return $this->email; 
    }
    
    public function isVerified(): bool { 
        return $this->verified; 
    }

    public function verifyPassword(string $plainPassword): bool {
        return password_verify($plainPassword, $this->passwordHash);
    }
}
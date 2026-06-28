<?php

declare(strict_types=1);

namespace LoginApp\Domain\ValueObjects;

class RegistrationData {
    private Name $name;
    private Email $email;
    private Password $password;

    public function __construct(Name $name, Email $email, Password $password) {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    public function getName(): Name {
        return $this->name;
    }

    public function getEmail(): Email {
        return $this->email;
    }

    public function getPassword(): Password {
        return $this->password;
    }
}
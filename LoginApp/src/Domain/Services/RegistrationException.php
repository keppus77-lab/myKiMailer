<?php

declare(strict_types=1);

namespace LoginApp\Domain\Services;

use Exception;

class RegistrationException extends Exception {
    const EMAIL_ALREADY_EXISTS = 7;
    const DATABASE_ERROR = 6;
    const VALIDATION_ERROR = 100;
}
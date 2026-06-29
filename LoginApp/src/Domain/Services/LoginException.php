<?php

declare(strict_types=1);

namespace LoginApp\Domain\Services;

use Exception;

class LoginException extends Exception {
    const INVALID_CREDENTIALS = 1;
    const DATABASE_ERROR = 2;
    const TOO_MANY_ATTEMPTS = 3;
    const NOT_VERIFIED = 4;
}
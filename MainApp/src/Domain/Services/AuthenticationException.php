<?php

declare(strict_types=1);

namespace MainApp\Domain\Services;

use Exception;

class AuthenticationException extends Exception {
    const INVALID_CREDENTIALS = 1;
    const USER_NOT_FOUND = 2;
    const USER_NOT_VERIFIED = 3;
    const ACCOUNT_LOCKED = 4;
}
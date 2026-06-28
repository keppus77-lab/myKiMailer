<?php

declare(strict_types=1);

namespace LoginApp\Domain\Services;

use Exception;

class EmailVerificationException extends Exception {
    const USER_NOT_FOUND = 5;
    const ALREADY_VERIFIED = 4;
    const TOO_MANY_REQUESTS = 3;
    const REQUEST_CREATION_FAILED = 2;
    const EMAIL_SEND_FAILED = 1;
}
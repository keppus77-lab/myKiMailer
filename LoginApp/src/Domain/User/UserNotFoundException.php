<?php

declare(strict_types=1);

namespace LoginApp\Domain\User;

use LoginApp\Domain\DomainException\DomainRecordNotFoundException;

class UserNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The user you requested does not exist.';
}

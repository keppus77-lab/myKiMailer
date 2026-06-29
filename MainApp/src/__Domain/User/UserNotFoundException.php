<?php

declare(strict_types=1);

namespace MainApp\Domain\User;

use MainApp\Domain\DomainException\DomainRecordNotFoundException;

class UserNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The user you requested does not exist.';
}

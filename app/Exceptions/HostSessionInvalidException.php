<?php

namespace App\Exceptions;

use Exception;

class HostSessionInvalidException extends Exception
{
    public function __construct(
        string $message = 'Host session is invalid or has expired.',
        int $code = 403
    ) {
        parent::__construct($message, $code);
    }
}

<?php

namespace App\Exceptions;

use Exception;

class HostAlreadyInMeetingException extends Exception
{
    public function __construct(
        protected ?string $activeMeetingCode = null,
        protected ?string $expiresAt = null,
        string $message = 'This account is already hosting another meeting.',
        int $code = 409
    ) {
        parent::__construct($message, $code);
    }

    public function getActiveMeetingCode(): ?string
    {
        return $this->activeMeetingCode;
    }

    public function getExpiresAt(): ?string
    {
        return $this->expiresAt;
    }
}

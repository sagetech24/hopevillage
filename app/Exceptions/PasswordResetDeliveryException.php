<?php

namespace App\Exceptions;

use RuntimeException;

class PasswordResetDeliveryException extends RuntimeException
{
    public static function message(string $message): self
    {
        return new self($message);
    }
}

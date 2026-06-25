<?php

declare(strict_types=1);

namespace MailSage\Exceptions;

use InvalidArgumentException;

class InvalidEmailException extends InvalidArgumentException
{
    public static function emptyEmail(): self
    {
        return new self('Email content cannot be empty.');
    }

    public static function malformedEmail(string $reason = ''): self
    {
        $message = 'The provided email is malformed and cannot be parsed.';
        if ($reason !== '') {
            $message .= " Reason: {$reason}";
        }

        return new self($message);
    }
}

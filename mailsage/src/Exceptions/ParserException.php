<?php

declare(strict_types=1);

namespace MailSage\Exceptions;

use RuntimeException;

class ParserException extends RuntimeException
{
    public static function mimeParsingFailed(string $reason = ''): self
    {
        $message = 'Failed to parse MIME structure.';
        if ($reason !== '') {
            $message .= " Reason: {$reason}";
        }

        return new self($message);
    }

    public static function headerParsingFailed(string $header): self
    {
        return new self("Failed to parse header: {$header}");
    }

    public static function unsupportedEncoding(string $encoding): self
    {
        return new self("Unsupported content encoding: {$encoding}");
    }
}

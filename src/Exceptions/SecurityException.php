<?php

declare(strict_types=1);

namespace MailSage\Exceptions;

use RuntimeException;

class SecurityException extends RuntimeException
{
    public static function dangerousAttachment(string $filename): self
    {
        return new self("Dangerous attachment detected: {$filename}");
    }

    public static function phishingDetected(): self
    {
        return new self('Phishing indicators detected in this email.');
    }

    public static function analysisError(string $reason): self
    {
        return new self("Security analysis error: {$reason}");
    }
}

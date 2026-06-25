<?php

declare(strict_types=1);

namespace MailSage\Exceptions;

use RuntimeException;

class SpamDetectionException extends RuntimeException
{
    public static function analysisError(string $reason): self
    {
        return new self("Spam analysis error: {$reason}");
    }

    public static function invalidScore(int $score): self
    {
        return new self("Spam score out of valid range (0-100): {$score}");
    }
}

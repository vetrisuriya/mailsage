<?php

declare(strict_types=1);

namespace MailSage\Exceptions;

use RuntimeException;

class AttachmentException extends RuntimeException
{
    public static function directoryNotWritable(string $directory): self
    {
        return new self("Attachment save directory is not writable: {$directory}");
    }

    public static function directoryNotFound(string $directory): self
    {
        return new self("Attachment save directory does not exist: {$directory}");
    }

    public static function saveFailed(string $filename): self
    {
        return new self("Failed to save attachment: {$filename}");
    }

    public static function invalidContent(): self
    {
        return new self('Attachment content is invalid or could not be decoded.');
    }
}

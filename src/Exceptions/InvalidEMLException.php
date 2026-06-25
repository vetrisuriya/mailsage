<?php

declare(strict_types=1);

namespace MailSage\Exceptions;

use RuntimeException;

class InvalidEMLException extends RuntimeException
{
    public static function fileNotFound(string $path): self
    {
        return new self("EML file not found: {$path}");
    }

    public static function notReadable(string $path): self
    {
        return new self("EML file is not readable: {$path}");
    }

    public static function invalidFormat(string $path): self
    {
        return new self("File does not appear to be a valid EML format: {$path}");
    }

    public static function emptyFile(string $path): self
    {
        return new self("EML file is empty: {$path}");
    }
}

<?php

declare(strict_types=1);

namespace MailSage\Contracts;

use MailSage\Models\Email;

interface EmailParserInterface
{
    /**
     * Parse a raw email string into an Email model.
     */
    public function parseEmail(string $rawEmail): Email;

    /**
     * Parse an EML file into an Email model.
     */
    public function parseFile(string $filePath): Email;
}

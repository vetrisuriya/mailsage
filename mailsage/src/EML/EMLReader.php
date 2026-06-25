<?php

declare(strict_types=1);

namespace MailSage\EML;

use MailSage\Exceptions\InvalidEMLException;

class EMLReader
{
    /**
     * Read an EML file from disk and return its raw content.
     *
     * @throws InvalidEMLException
     */
    public function read(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw InvalidEMLException::fileNotFound($filePath);
        }

        if (!is_readable($filePath)) {
            throw InvalidEMLException::notReadable($filePath);
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw InvalidEMLException::notReadable($filePath);
        }

        if (trim($content) === '') {
            throw InvalidEMLException::emptyFile($filePath);
        }

        if (!$this->looksLikeEmail($content)) {
            throw InvalidEMLException::invalidFormat($filePath);
        }

        return $content;
    }

    /**
     * Perform a basic sanity check that the content looks like an email.
     * An email must have at least some header-like lines.
     */
    private function looksLikeEmail(string $content): bool
    {
        $firstChunk = substr($content, 0, 2000);

        // Must contain at least one recognizable email header
        $coreHeaders = [
            'From:',
            'To:',
            'Subject:',
            'Date:',
            'Message-ID:',
            'MIME-Version:',
            'Content-Type:',
            'Return-Path:',
            'Received:',
        ];

        foreach ($coreHeaders as $header) {
            if (stripos($firstChunk, $header) !== false) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace MailSage\Helpers;

class StringHelper
{
    /**
     * Decode MIME encoded-word syntax (RFC 2047).
     * e.g. =?UTF-8?B?base64data?= or =?UTF-8?Q?quoted_printable?=
     */
    public static function decodeMimeEncodedWord(string $text): string
    {
        if (!str_contains($text, '=?')) {
            return $text;
        }

        return mb_decode_mimeheader($text) ?: $text;
    }

    /**
     * Decode base64 content, returning empty string on failure.
     */
    public static function decodeBase64(string $data): string
    {
        $decoded = base64_decode(str_replace(["\r", "\n", ' '], '', $data), true);

        return $decoded !== false ? $decoded : '';
    }

    /**
     * Decode quoted-printable content.
     */
    public static function decodeQuotedPrintable(string $data): string
    {
        return quoted_printable_decode($data);
    }

    /**
     * Normalize line endings to \n.
     */
    public static function normalizeLineEndings(string $text): string
    {
        return str_replace(["\r\n", "\r"], "\n", $text);
    }

    /**
     * Strip HTML tags and decode HTML entities from body text.
     */
    public static function htmlToPlainText(string $html): string
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/p>/i', "\n\n", $html) ?? $html;
        $html = preg_replace('/<\/div>/i', "\n", $html) ?? $html;
        $html = strip_tags($html);

        return html_entity_decode(trim($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Extract all URLs from a string.
     *
     * @return string[]
     */
    public static function extractUrls(string $text): array
    {
        preg_match_all(
            '/https?:\/\/[^\s<>"\')\]]+/i',
            $text,
            $matches
        );

        return array_unique($matches[0]);
    }

    /**
     * Extract all email addresses from a string.
     *
     * @return string[]
     */
    public static function extractEmailAddresses(string $text): array
    {
        preg_match_all(
            '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
            $text,
            $matches
        );

        return array_unique($matches[0]);
    }

    /**
     * Extract all phone numbers from a string.
     *
     * @return string[]
     */
    public static function extractPhoneNumbers(string $text): array
    {
        preg_match_all(
            '/(?:\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/',
            $text,
            $matches
        );

        return array_unique($matches[0]);
    }

    /**
     * Count words in a string.
     */
    public static function wordCount(string $text): int
    {
        return str_word_count(strip_tags($text));
    }

    /**
     * Count occurrences of all-caps words in a string.
     */
    public static function capsWordCount(string $text): int
    {
        preg_match_all('/\b[A-Z]{3,}\b/', $text, $matches);

        return count($matches[0]);
    }

    /**
     * Sanitize a filename for safe filesystem use.
     */
    public static function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^\w\-\.]/', '_', $filename) ?? $filename;

        return ltrim($filename, '.');
    }

    /**
     * Get the file extension from a filename.
     */
    public static function getExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Extract domain from an email address.
     */
    public static function domainFromEmail(string $email): string
    {
        $parts = explode('@', $email);

        return strtolower(end($parts));
    }

    /**
     * Check whether a string contains any of the given keywords (case-insensitive).
     *
     * @param string[] $keywords
     */
    public static function containsAny(string $text, array $keywords): bool
    {
        $lowerText = strtolower($text);
        foreach ($keywords as $keyword) {
            if (str_contains($lowerText, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count how many of the given keywords appear in the text.
     *
     * @param string[] $keywords
     */
    public static function countKeywordHits(string $text, array $keywords): int
    {
        $lowerText = strtolower($text);
        $count = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($lowerText, strtolower($keyword))) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Truncate a string to the given length.
     */
    public static function truncate(string $text, int $length = 200, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . $suffix;
    }
}

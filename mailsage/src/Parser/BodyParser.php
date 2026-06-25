<?php

declare(strict_types=1);

namespace MailSage\Parser;

use MailSage\Helpers\StringHelper;

class BodyParser
{
    /**
     * Decode a MIME body part based on its Content-Transfer-Encoding.
     */
    public function decode(string $content, string $encoding): string
    {
        return match (strtolower(trim($encoding))) {
            'base64'           => StringHelper::decodeBase64($content),
            'quoted-printable' => StringHelper::decodeQuotedPrintable($content),
            '7bit', '8bit', 'binary', '' => $content,
            default            => $content,
        };
    }

    /**
     * Convert charset-encoded content to UTF-8.
     */
    public function toUtf8(string $content, string $charset): string
    {
        $charset = strtolower(trim($charset));

        if ($charset === '' || $charset === 'utf-8' || $charset === 'utf8') {
            return $content;
        }

        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($content, 'UTF-8', $charset);
            if ($converted !== false) {
                return $converted;
            }
        }

        if (function_exists('iconv')) {
            $converted = @iconv($charset, 'UTF-8//IGNORE', $content);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $content;
    }

    /**
     * Extract plain text from HTML body as a fallback.
     */
    public function htmlToPlain(string $html): string
    {
        return StringHelper::htmlToPlainText($html);
    }

    /**
     * Clean up a plain text body: normalize whitespace and trim.
     */
    public function cleanPlainText(string $text): string
    {
        // Remove excessive blank lines (more than 2)
        $text = preg_replace("/(\n\s*){3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}

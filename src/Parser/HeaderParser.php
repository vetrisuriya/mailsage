<?php

declare(strict_types=1);

namespace MailSage\Parser;

use MailSage\Helpers\StringHelper;

class HeaderParser
{
    /**
     * Parse the raw headers section of an email into a key-value map.
     * Header names are normalized to lowercase.
     * Multi-line (folded) headers are unfolded.
     *
     * @return array<string, string|string[]>
     */
    public function parse(string $rawHeaders): array
    {
        $headers = [];
        $unfolded = $this->unfold($rawHeaders);
        $lines    = explode("\n", $unfolded);

        foreach ($lines as $line) {
            if (trim($line) === '' || !str_contains($line, ':')) {
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue;
            }

            $name  = strtolower(trim(substr($line, 0, $colonPos)));
            $value = trim(substr($line, $colonPos + 1));

            if ($name === '') {
                continue;
            }

            // Some headers may appear multiple times (e.g. Received)
            if (isset($headers[$name])) {
                if (!is_array($headers[$name])) {
                    $headers[$name] = [$headers[$name]];
                }
                $headers[$name][] = $value;
            } else {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Unfold multi-line (folded) header values per RFC 5322.
     * A folded header starts with whitespace on the continuation line.
     */
    private function unfold(string $rawHeaders): string
    {
        // Normalize CRLF/CR to LF
        $text = StringHelper::normalizeLineEndings($rawHeaders);
        // Unfold: a newline followed by whitespace is a continuation
        return preg_replace("/\n[ \t]+/", ' ', $text) ?? $text;
    }

    /**
     * Decode a header value that may contain RFC 2047 encoded words.
     */
    public function decodeValue(string $value): string
    {
        return StringHelper::decodeMimeEncodedWord($value);
    }

    /**
     * Split the raw email into headers section and body section.
     *
     * @return array{headers: string, body: string}
     */
    public function splitHeadersAndBody(string $rawEmail): array
    {
        $normalized = StringHelper::normalizeLineEndings($rawEmail);

        // Headers and body are separated by a blank line
        $pos = strpos($normalized, "\n\n");

        if ($pos === false) {
            return ['headers' => $normalized, 'body' => ''];
        }

        return [
            'headers' => substr($normalized, 0, $pos),
            'body'    => substr($normalized, $pos + 2),
        ];
    }

    /**
     * Extract SPF result from Authentication-Results header.
     */
    public function extractSpfResult(string $authResults): ?string
    {
        if (preg_match('/spf=(\w+)/i', $authResults, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    /**
     * Extract DKIM result from Authentication-Results header.
     */
    public function extractDkimResult(string $authResults): ?string
    {
        if (preg_match('/dkim=(\w+)/i', $authResults, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    /**
     * Extract DMARC result from Authentication-Results header.
     */
    public function extractDmarcResult(string $authResults): ?string
    {
        if (preg_match('/dmarc=(\w+)/i', $authResults, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }
}

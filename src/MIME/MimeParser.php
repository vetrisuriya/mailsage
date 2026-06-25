<?php

declare(strict_types=1);

namespace MailSage\MIME;

use MailSage\Helpers\StringHelper;
use MailSage\Parser\BodyParser;

class MimeParser
{
    private BodyParser $bodyParser;

    public function __construct()
    {
        $this->bodyParser = new BodyParser();
    }

    /**
     * Parse a MIME message body into a structured tree.
     *
     * @param array<string, string|string[]> $headers  Top-level parsed headers
     * @return array{
     *   text_plain: string,
     *   text_html: string,
     *   attachments: array<int, array{name: string, mime_type: string, encoding: string, content: string, size: int}>
     * }
     */
    public function parse(array $headers, string $body): array
    {
        $contentType = is_array($headers['content-type'] ?? '')
            ? (string) ($headers['content-type'][0] ?? '')
            : (string) ($headers['content-type'] ?? '');

        $result = [
            'text_plain'  => '',
            'text_html'   => '',
            'attachments' => [],
        ];

        $this->processPart($contentType, $headers, $body, $result);

        return $result;
    }

    /**
     * @param array<string, string|string[]> $headers
     * @param array{text_plain: string, text_html: string, attachments: array<int, mixed>} $result
     */
    private function processPart(string $contentType, array $headers, string $body, array &$result): void
    {
        $mimeType = $this->extractMimeType($contentType);
        $charset  = $this->extractParam($contentType, 'charset') ?: 'utf-8';
        $encoding = is_array($headers['content-transfer-encoding'] ?? '')
            ? (string) ($headers['content-transfer-encoding'][0] ?? '')
            : (string) ($headers['content-transfer-encoding'] ?? '');

        if (str_starts_with($mimeType, 'multipart/')) {
            $boundary = $this->extractParam($contentType, 'boundary');
            if ($boundary !== '') {
                $parts = $this->splitMultipart($body, $boundary);
                foreach ($parts as $part) {
                    [$partHeaders, $partBody] = $this->splitPart($part);
                    $partContentType = is_array($partHeaders['content-type'] ?? '')
                        ? (string) ($partHeaders['content-type'][0] ?? '')
                        : (string) ($partHeaders['content-type'] ?? 'text/plain');
                    $this->processPart($partContentType, $partHeaders, $partBody, $result);
                }
            }

            return;
        }

        $disposition = is_array($headers['content-disposition'] ?? '')
            ? (string) ($headers['content-disposition'][0] ?? '')
            : (string) ($headers['content-disposition'] ?? '');

        $isAttachment = str_contains(strtolower($disposition), 'attachment')
            || str_contains(strtolower($disposition), 'inline') && $this->hasFilename($disposition, $contentType);

        if ($isAttachment) {
            $filename = $this->extractFilename($disposition, $contentType, $headers);
            $decoded  = $this->bodyParser->decode($body, $encoding);
            $result['attachments'][] = [
                'name'      => $filename,
                'mime_type' => $mimeType,
                'encoding'  => $encoding,
                'content'   => $decoded,
                'size'      => strlen($decoded),
            ];

            return;
        }

        $decoded = $this->bodyParser->decode($body, $encoding);
        $decoded = $this->bodyParser->toUtf8($decoded, $charset);

        if ($mimeType === 'text/plain' && $result['text_plain'] === '') {
            $result['text_plain'] = $this->bodyParser->cleanPlainText($decoded);
        } elseif ($mimeType === 'text/html' && $result['text_html'] === '') {
            $result['text_html'] = $decoded;
        }
    }

    /**
     * Split a multipart body into individual part strings.
     *
     * @return string[]
     */
    private function splitMultipart(string $body, string $boundary): array
    {
        $body   = StringHelper::normalizeLineEndings($body);
        $delimiter = "--{$boundary}";
        $endDelim  = "--{$boundary}--";

        $parts = [];
        $lines = explode("\n", $body);
        $current   = null;
        $inPart    = false;

        foreach ($lines as $line) {
            if (rtrim($line) === $endDelim) {
                if ($current !== null) {
                    $parts[] = implode("\n", $current);
                }
                break;
            }

            if (rtrim($line) === $delimiter) {
                if ($current !== null && $inPart) {
                    $parts[] = implode("\n", $current);
                }
                $current = [];
                $inPart  = true;
                continue;
            }

            if ($inPart && $current !== null) {
                $current[] = $line;
            }
        }

        return $parts;
    }

    /**
     * Split a MIME part into its headers and body.
     *
     * @return array{array<string, string|string[]>, string}
     */
    private function splitPart(string $part): array
    {
        $normalized = StringHelper::normalizeLineEndings($part);
        $pos = strpos($normalized, "\n\n");

        if ($pos === false) {
            return [[], $normalized];
        }

        $headerSection = substr($normalized, 0, $pos);
        $body          = substr($normalized, $pos + 2);

        // Unfold and parse minimal headers for MIME parts
        $headerSection = preg_replace("/\n[ \t]+/", ' ', $headerSection) ?? $headerSection;

        $headers = [];
        foreach (explode("\n", $headerSection) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            $colonPos = strpos($line, ':');
            $name  = strtolower(trim(substr($line, 0, $colonPos)));
            $value = trim(substr($line, $colonPos + 1));
            if ($name !== '') {
                $headers[$name] = $value;
            }
        }

        return [$headers, $body];
    }

    /**
     * Extract the MIME type (e.g. "text/plain") from a Content-Type value.
     */
    private function extractMimeType(string $contentType): string
    {
        $parts = explode(';', $contentType);

        return strtolower(trim($parts[0]));
    }

    /**
     * Extract a parameter value from a Content-Type or Content-Disposition header.
     * e.g. extractParam('text/plain; charset=UTF-8', 'charset') => 'UTF-8'
     */
    private function extractParam(string $header, string $param): string
    {
        if (preg_match('/' . preg_quote($param, '/') . '\s*=\s*"?([^";]+)"?/i', $header, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * Determine if a content-disposition or content-type has a filename.
     */
    private function hasFilename(string $disposition, string $contentType): bool
    {
        return $this->extractParam($disposition, 'filename') !== ''
            || $this->extractParam($contentType, 'name') !== '';
    }

    /**
     * Extract the filename from headers.
     *
     * @param array<string, string|string[]> $headers
     */
    private function extractFilename(string $disposition, string $contentType, array $headers): string
    {
        $filename = $this->extractParam($disposition, 'filename');
        if ($filename === '') {
            $filename = $this->extractParam($contentType, 'name');
        }
        if ($filename === '') {
            $filename = 'attachment_' . time();
        }

        $filename = mb_decode_mimeheader($filename) ?: $filename;

        return StringHelper::sanitizeFilename($filename);
    }
}

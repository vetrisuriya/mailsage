<?php

declare(strict_types=1);

namespace MailSage;

use MailSage\Attachment\AttachmentManager;
use MailSage\Contracts\EmailParserInterface;
use MailSage\EML\EMLReader;
use MailSage\Exceptions\InvalidEmailException;
use MailSage\MIME\MimeParser;
use MailSage\Models\Email;
use MailSage\Parser\AddressParser;
use MailSage\Parser\HeaderParser;

class EmailParser implements EmailParserInterface
{
    private HeaderParser  $headerParser;
    private AddressParser $addressParser;
    private MimeParser    $mimeParser;
    private EMLReader     $emlReader;

    public function __construct()
    {
        $this->headerParser  = new HeaderParser();
        $this->addressParser = new AddressParser();
        $this->mimeParser    = new MimeParser();
        $this->emlReader     = new EMLReader();
    }

    /**
     * Static factory: parse a raw email string.
     *
     * @throws InvalidEmailException
     */
    public static function parse(string $rawEmail): Email
    {
        return (new self())->doParse($rawEmail);
    }

    /**
     * Static factory: parse an EML file.
     *
     * @throws \MailSage\Exceptions\InvalidEMLException
     * @throws InvalidEmailException
     */
    public static function fromFile(string $filePath): Email
    {
        $instance = new self();
        $raw      = $instance->emlReader->read($filePath);

        return $instance->doParse($raw);
    }

    /**
     * Instance method implementing interface: parse a raw email string.
     *
     * @throws InvalidEmailException
     */
    public function parseEmail(string $rawEmail): Email
    {
        return $this->doParse($rawEmail);
    }

    /**
     * Instance method implementing interface: parse an EML file.
     *
     * @throws \MailSage\Exceptions\InvalidEMLException
     * @throws InvalidEmailException
     */
    public function parseFile(string $filePath): Email
    {
        $raw = $this->emlReader->read($filePath);

        return $this->doParse($raw);
    }

    /**
     * Core parsing pipeline.
     *
     * @throws InvalidEmailException
     */
    private function doParse(string $rawEmail): Email
    {
        if (trim($rawEmail) === '') {
            throw InvalidEmailException::emptyEmail();
        }

        // 1. Split headers from body
        ['headers' => $rawHeaders, 'body' => $rawBody] = $this->headerParser->splitHeadersAndBody($rawEmail);

        // 2. Parse headers into a key-value map
        $headers = $this->headerParser->parse($rawHeaders);

        // 3. Decode header values
        $subject   = $this->decodeHeader($headers, 'subject');
        $fromRaw   = $this->decodeHeader($headers, 'from');
        $toRaw     = $this->decodeHeader($headers, 'to');
        $ccRaw     = $this->decodeHeader($headers, 'cc');
        $bccRaw    = $this->decodeHeader($headers, 'bcc');
        $date      = $this->decodeHeader($headers, 'date');
        $messageId = $this->decodeHeader($headers, 'message-id');

        // 4. Parse addresses
        $sender    = $this->addressParser->parseSingle($fromRaw);
        $recipient = $this->addressParser->parseList($toRaw);
        $cc        = $this->addressParser->parseList($ccRaw);
        $bcc       = $this->addressParser->parseList($bccRaw);

        // 5. Parse MIME structure
        $contentType = $this->decodeHeader($headers, 'content-type');
        $mime        = $this->mimeParser->parse($headers, $rawBody);

        // 6. Build attachment manager
        $attachmentManager = new AttachmentManager($mime['attachments']);

        // 7. Metadata
        $metadata = [
            'raw_size'         => strlen($rawEmail),
            'has_html'         => $mime['text_html'] !== '',
            'has_plain'        => $mime['text_plain'] !== '',
            'attachment_count' => count($mime['attachments']),
            'content_type'     => $contentType,
        ];

        return new Email(
            subject:           $subject,
            sender:            $sender,
            recipient:         $recipient,
            cc:                $cc,
            bcc:               $bcc,
            date:              $date,
            messageId:         $messageId,
            body:              $mime['text_plain'],
            htmlBody:          $mime['text_html'],
            attachmentManager: $attachmentManager,
            headers:           $headers,
            metadata:          $metadata,
        );
    }

    /**
     * Safely read a header value from the map, returning an empty string if absent.
     *
     * @param array<string, string|string[]> $headers
     */
    private function decodeHeader(array $headers, string $name): string
    {
        $value = $headers[strtolower($name)] ?? '';

        if (is_array($value)) {
            $value = $value[0] ?? '';
        }

        return $this->headerParser->decodeValue((string) $value);
    }
}

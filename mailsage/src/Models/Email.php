<?php

declare(strict_types=1);

namespace MailSage\Models;

use MailSage\Attachment\AttachmentManager;
use MailSage\Categorization\CategoryDetector;
use MailSage\Invoice\Invoice;
use MailSage\Invoice\InvoiceDetector;
use MailSage\Lead\Lead;
use MailSage\Lead\LeadDetector;
use MailSage\Order\Order;
use MailSage\Order\OrderDetector;
use MailSage\Security\SecurityAnalyzer;
use MailSage\Spam\SpamDetector;
use MailSage\Support\SupportDetector;

class Email
{
    private ?SpamDetector     $spamDetector     = null;
    private ?InvoiceDetector  $invoiceDetector  = null;
    private ?OrderDetector    $orderDetector    = null;
    private ?LeadDetector     $leadDetector     = null;
    private ?SupportDetector  $supportDetector  = null;
    private ?SecurityAnalyzer $securityAnalyzer = null;
    private ?CategoryDetector $categoryDetector = null;

    // Cached analysis results
    private ?int   $cachedSpamScore   = null;
    private ?array $cachedSecReport   = null;
    private ?array $cachedCatScores   = null;

    /**
     * @param array{name: string, email: string, raw: string}                    $sender
     * @param array<int, array{name: string, email: string, raw: string}>         $recipient
     * @param array<int, array{name: string, email: string, raw: string}>         $cc
     * @param array<int, array{name: string, email: string, raw: string}>         $bcc
     * @param array<string, string|string[]>                                      $headers
     */
    public function __construct(
        private readonly string            $subject,
        private readonly array             $sender,
        private readonly array             $recipient,
        private readonly array             $cc,
        private readonly array             $bcc,
        private readonly string            $date,
        private readonly string            $messageId,
        private readonly string            $body,
        private readonly string            $htmlBody,
        private readonly AttachmentManager $attachmentManager,
        private readonly array             $headers,
        private readonly array             $metadata = [],
    ) {}

    // ─── Basic Fields ────────────────────────────────────────────────────────

    public function subject(): string
    {
        return $this->subject;
    }

    /**
     * @return array{name: string, email: string, raw: string}
     */
    public function sender(): array
    {
        return $this->sender;
    }

    /**
     * @return array<int, array{name: string, email: string, raw: string}>
     */
    public function recipient(): array
    {
        return $this->recipient;
    }

    /**
     * @return array<int, array{name: string, email: string, raw: string}>
     */
    public function cc(): array
    {
        return $this->cc;
    }

    /**
     * @return array<int, array{name: string, email: string, raw: string}>
     */
    public function bcc(): array
    {
        return $this->bcc;
    }

    public function date(): string
    {
        return $this->date;
    }

    public function messageId(): string
    {
        return $this->messageId;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function htmlBody(): string
    {
        return $this->htmlBody;
    }

    /**
     * @return array<string, string|string[]>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): string|array|null
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function attachments(): AttachmentManager
    {
        return $this->attachmentManager;
    }

    /**
     * Save all attachments to a directory.
     *
     * @return string[]
     */
    public function saveAttachments(string $directory): array
    {
        return $this->attachmentManager->saveAll($directory);
    }

    // ─── Header / Auth Report ────────────────────────────────────────────────

    /**
     * @return array{
     *   message_id: string,
     *   return_path: string,
     *   spf: string|null,
     *   dkim: string|null,
     *   dmarc: string|null,
     *   received: string[]
     * }
     */
    public function headerReport(): array
    {
        $authResults = (string) ($this->headers['authentication-results'] ?? '');
        $received    = $this->headers['received'] ?? [];

        return [
            'message_id'  => $this->messageId,
            'return_path' => (string) ($this->headers['return-path'] ?? ''),
            'spf'         => $this->extractAuthResult($authResults, 'spf'),
            'dkim'        => $this->extractAuthResult($authResults, 'dkim'),
            'dmarc'       => $this->extractAuthResult($authResults, 'dmarc'),
            'received'    => is_array($received) ? $received : ([$received] ?: []),
        ];
    }

    private function extractAuthResult(string $authResults, string $protocol): ?string
    {
        if (preg_match('/' . preg_quote($protocol, '/') . '=(\w+)/i', $authResults, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    // ─── Spam ────────────────────────────────────────────────────────────────

    public function isSpam(): bool
    {
        return $this->spamScore() >= 50;
    }

    public function spamScore(): int
    {
        if ($this->cachedSpamScore === null) {
            $this->cachedSpamScore = $this->getSpamDetector()->score($this);
        }

        return $this->cachedSpamScore;
    }

    // ─── Phishing ────────────────────────────────────────────────────────────

    public function isPhishing(): bool
    {
        return $this->securityReport()['is_phishing'];
    }

    // ─── Invoice ─────────────────────────────────────────────────────────────

    public function isInvoice(): bool
    {
        return $this->getInvoiceDetector()->detect($this);
    }

    public function invoice(): Invoice
    {
        return $this->getInvoiceDetector()->extract($this);
    }

    // ─── Order ───────────────────────────────────────────────────────────────

    public function isOrder(): bool
    {
        return $this->getOrderDetector()->detect($this);
    }

    public function order(): Order
    {
        return $this->getOrderDetector()->extract($this);
    }

    // ─── Lead ────────────────────────────────────────────────────────────────

    public function isLead(): bool
    {
        return $this->getLeadDetector()->detect($this);
    }

    public function lead(): Lead
    {
        return $this->getLeadDetector()->extract($this);
    }

    // ─── Support ─────────────────────────────────────────────────────────────

    public function isSupportRequest(): bool
    {
        return $this->getSupportDetector()->detect($this);
    }

    public function supportSubCategory(): string
    {
        return $this->getSupportDetector()->subCategory($this);
    }

    // ─── Category ────────────────────────────────────────────────────────────

    public function category(): string
    {
        return $this->getCategoryDetector()->detect($this);
    }

    public function confidence(): int
    {
        return $this->getCategoryDetector()->confidence($this);
    }

    // ─── Security ────────────────────────────────────────────────────────────

    /**
     * @return array{
     *   is_phishing: bool,
     *   phishing_confidence: int,
     *   has_dangerous_attachment: bool,
     *   attachment_risk: string,
     *   suspicious_urls: string[],
     *   phishing_indicators: string[],
     *   sender_mismatch: bool,
     *   overall_risk: string
     * }
     */
    public function securityReport(): array
    {
        if ($this->cachedSecReport === null) {
            $this->cachedSecReport = $this->getSecurityAnalyzer()->analyze($this);
        }

        return $this->cachedSecReport;
    }

    public function attachmentRisk(): string
    {
        return $this->attachmentManager->highestRiskLevel();
    }

    // ─── Export ──────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subject'          => $this->subject,
            'sender'           => $this->sender,
            'recipient'        => $this->recipient,
            'cc'               => $this->cc,
            'bcc'              => $this->bcc,
            'date'             => $this->date,
            'message_id'       => $this->messageId,
            'body'             => $this->body,
            'html_body'        => $this->htmlBody,
            'attachments'      => $this->attachmentManager->toArray(),
            'headers'          => $this->headers,
            'metadata'         => $this->metadata,
        ];
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $flags) ?: '{}';
    }

    public function toCsv(): string
    {
        $data   = $this->toArray();
        $output = fopen('php://temp', 'r+b');
        if ($output === false) {
            return '';
        }

        $flatRow = [
            'subject'    => $data['subject'],
            'from_name'  => $data['sender']['name'] ?? '',
            'from_email' => $data['sender']['email'] ?? '',
            'to'         => implode('; ', array_column($data['recipient'], 'email')),
            'date'       => $data['date'],
            'message_id' => $data['message_id'],
            'body'       => substr($data['body'], 0, 500),
            'attachments'=> count($data['attachments']),
        ];

        fputcsv($output, array_keys($flatRow));
        fputcsv($output, array_values($flatRow));
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }

    // ─── Lazy Service Accessors ───────────────────────────────────────────────

    private function getSpamDetector(): SpamDetector
    {
        return $this->spamDetector ??= new SpamDetector();
    }

    private function getInvoiceDetector(): InvoiceDetector
    {
        return $this->invoiceDetector ??= new InvoiceDetector();
    }

    private function getOrderDetector(): OrderDetector
    {
        return $this->orderDetector ??= new OrderDetector();
    }

    private function getLeadDetector(): LeadDetector
    {
        return $this->leadDetector ??= new LeadDetector();
    }

    private function getSupportDetector(): SupportDetector
    {
        return $this->supportDetector ??= new SupportDetector();
    }

    private function getSecurityAnalyzer(): SecurityAnalyzer
    {
        return $this->securityAnalyzer ??= new SecurityAnalyzer();
    }

    private function getCategoryDetector(): CategoryDetector
    {
        return $this->categoryDetector ??= new CategoryDetector();
    }
}

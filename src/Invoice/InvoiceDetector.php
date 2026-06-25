<?php

declare(strict_types=1);

namespace MailSage\Invoice;

use MailSage\Contracts\DetectorInterface;
use MailSage\Helpers\RegexPatterns;
use MailSage\Helpers\StringHelper;
use MailSage\Models\Email;

class InvoiceDetector implements DetectorInterface
{
    private const SUBJECT_KEYWORDS = [
        'invoice', 'inv-', 'bill', 'receipt', 'tax invoice',
        'payment receipt', 'payment confirmation', 'your invoice',
        'statement of account', 'proforma', 'purchase order',
    ];

    private const BODY_KEYWORDS = [
        'invoice number', 'invoice #', 'bill to', 'invoice date',
        'due date', 'payment due', 'total amount due', 'subtotal',
        'tax amount', 'grand total', 'please pay', 'amount payable',
        'thank you for your payment', 'your receipt',
    ];

    private const CURRENCY_MAP = [
        '$'  => 'USD',
        '€'  => 'EUR',
        '£'  => 'GBP',
        '₹'  => 'INR',
        '¥'  => 'JPY',
        'A$' => 'AUD',
        'C$' => 'CAD',
    ];

    public function detect(Email $email): bool
    {
        return $this->confidence($email) >= 40;
    }

    public function confidence(Email $email): int
    {
        $score   = 0;
        $subject = strtolower($email->subject());
        $body    = strtolower($email->body() . ' ' . strip_tags($email->htmlBody()));

        // Subject keyword hits
        foreach (self::SUBJECT_KEYWORDS as $kw) {
            if (str_contains($subject, $kw)) {
                $score += 30;
                break;
            }
        }

        // Body keyword hits
        $bodyHits = StringHelper::countKeywordHits($body, self::BODY_KEYWORDS);
        $score += min($bodyHits * 10, 40);

        // Has invoice number pattern
        if (preg_match(RegexPatterns::INVOICE_NUMBER, $email->subject() . ' ' . $email->body())) {
            $score += 15;
        }

        // Has amount/currency
        if (preg_match(RegexPatterns::INVOICE_AMOUNT, $email->body())) {
            $score += 10;
        }

        // Attachment named like an invoice
        foreach ($email->attachments()->all() as $attachment) {
            $name = strtolower($attachment->name());
            if (StringHelper::containsAny($name, ['invoice', 'receipt', 'bill'])) {
                $score += 15;
            }
        }

        return min($score, 100);
    }

    public function extract(Email $email): Invoice
    {
        $text = $email->subject() . "\n" . $email->body() . "\n" . strip_tags($email->htmlBody());

        return new Invoice(
            number:   $this->extractNumber($text),
            date:     $this->extractDate($text),
            amount:   $this->extractAmount($text),
            currency: $this->extractCurrency($text),
            vendor:   $this->extractVendor($email),
        );
    }

    private function extractNumber(string $text): ?string
    {
        if (preg_match(RegexPatterns::INVOICE_NUMBER, $text, $m)) {
            return strtoupper(trim($m[1]));
        }

        return null;
    }

    private function extractDate(string $text): ?string
    {
        if (preg_match(RegexPatterns::INVOICE_DATE, $text, $m)) {
            return trim($m[1]);
        }
        if (preg_match(RegexPatterns::GENERIC_DATE, $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractAmount(string $text): ?float
    {
        if (preg_match(RegexPatterns::INVOICE_AMOUNT, $text, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }
        if (preg_match(RegexPatterns::INVOICE_AMOUNT_ALT, $text, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }

        return null;
    }

    private function extractCurrency(string $text): ?string
    {
        if (preg_match(RegexPatterns::CURRENCY_CODE, $text, $m)) {
            return strtoupper($m[1]);
        }
        foreach (self::CURRENCY_MAP as $symbol => $code) {
            if (str_contains($text, $symbol)) {
                return $code;
            }
        }

        return null;
    }

    private function extractVendor(Email $email): ?string
    {
        $sender = $email->sender();
        if (!empty($sender['name'])) {
            return $sender['name'];
        }
        if (!empty($sender['email'])) {
            return StringHelper::domainFromEmail($sender['email']);
        }

        return null;
    }
}

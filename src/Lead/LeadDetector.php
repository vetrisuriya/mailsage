<?php

declare(strict_types=1);

namespace MailSage\Lead;

use MailSage\Contracts\DetectorInterface;
use MailSage\Helpers\RegexPatterns;
use MailSage\Helpers\StringHelper;
use MailSage\Models\Email;

class LeadDetector implements DetectorInterface
{
    private const LEAD_KEYWORDS = [
        'interested in', 'inquiry', 'enquiry', 'i would like to',
        'please contact me', 'get in touch', 'looking for',
        'need a quote', 'request a demo', 'schedule a call',
        'partnership opportunity', 'business proposal', 'collaboration',
        'services you offer', 'your pricing', 'more information',
        'contact form', 'web form submission',
    ];

    public function detect(Email $email): bool
    {
        return $this->confidence($email) >= 35;
    }

    public function confidence(Email $email): int
    {
        $score = 0;
        $body  = $email->body() . ' ' . strip_tags($email->htmlBody());
        $full  = $email->subject() . ' ' . $body;

        $hits = StringHelper::countKeywordHits($full, self::LEAD_KEYWORDS);
        $score += min($hits * 12, 40);

        // Has contact details
        if (preg_match(RegexPatterns::LEAD_NAME_FIELD, $body)) {
            $score += 15;
        }
        if (preg_match(RegexPatterns::LEAD_COMPANY, $body)) {
            $score += 15;
        }
        if (preg_match(RegexPatterns::LEAD_PHONE, $body)) {
            $score += 10;
        }
        if (preg_match(RegexPatterns::EMAIL_ADDRESS, $body)) {
            $score += 5;
        }

        // Came from a contact form (typical subject patterns)
        $subject = strtolower($email->subject());
        if (
            str_contains($subject, 'contact') ||
            str_contains($subject, 'inquiry') ||
            str_contains($subject, 'enquiry') ||
            str_contains($subject, 'quote request')
        ) {
            $score += 20;
        }

        return min($score, 100);
    }

    public function extract(Email $email): Lead
    {
        $body = $email->body() . "\n" . strip_tags($email->htmlBody());

        return new Lead(
            name:    $this->extractName($email, $body),
            email:   $this->extractEmail($email, $body),
            phone:   $this->extractPhone($body),
            company: $this->extractCompany($body),
        );
    }

    private function extractName(Email $email, string $body): ?string
    {
        if (preg_match(RegexPatterns::LEAD_NAME_FIELD, $body, $m)) {
            return trim($m[1]);
        }

        // Fall back to sender name
        $sender = $email->sender();
        if (!empty($sender['name'])) {
            return $sender['name'];
        }

        return null;
    }

    private function extractEmail(Email $email, string $body): ?string
    {
        // Try to find email in body (contact form style)
        $emails = StringHelper::extractEmailAddresses($body);
        $senderEmail = $email->sender()['email'] ?? '';

        foreach ($emails as $addr) {
            if ($addr !== $senderEmail) {
                return $addr;
            }
        }

        return $senderEmail ?: null;
    }

    private function extractPhone(string $body): ?string
    {
        if (preg_match(RegexPatterns::LEAD_PHONE, $body, $m)) {
            return trim($m[1]);
        }
        $phones = StringHelper::extractPhoneNumbers($body);

        return $phones[0] ?? null;
    }

    private function extractCompany(string $body): ?string
    {
        if (preg_match(RegexPatterns::LEAD_COMPANY, $body, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}

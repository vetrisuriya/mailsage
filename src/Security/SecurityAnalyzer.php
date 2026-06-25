<?php

declare(strict_types=1);

namespace MailSage\Security;

use MailSage\Helpers\StringHelper;
use MailSage\Models\Email;

class SecurityAnalyzer
{
    private const DANGEROUS_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'scr', 'pif', 'com',
        'vbs', 'vbe', 'js', 'jse', 'ws', 'wsf', 'wsc', 'wsh',
        'ps1', 'ps1xml', 'ps2', 'ps2xml', 'psc1', 'psc2',
        'msh', 'msh1', 'msh2', 'mshxml', 'msh1xml', 'msh2xml',
        'msi', 'msp', 'reg', 'inf', 'dll', 'sys',
        'lnk', 'url', 'hta', 'cpl', 'jar',
    ];

    private const PHISHING_INDICATORS = [
        'verify your account', 'confirm your identity', 'validate your account',
        'update your payment', 'your account has been compromised',
        'click here to avoid suspension', 'action required immediately',
        'your account will be terminated', 'unusual sign-in activity',
        'we noticed suspicious activity',
    ];

    private const SHORTENERS = [
        'bit.ly', 'tinyurl.com', 't.co', 'goo.gl', 'ow.ly',
        'is.gd', 'buff.ly', 'adf.ly', 'short.link', 'rb.gy', 'cutt.ly',
    ];

    /**
     * Perform a full security analysis of the email.
     *
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
    public function analyze(Email $email): array
    {
        $phishingIndicators  = $this->detectPhishingIndicators($email);
        $phishingConfidence  = $this->phishingScore($email, $phishingIndicators);
        $hasDangerousAttach  = $email->attachments()->hasDangerousAttachment();
        $attachmentRisk      = $email->attachments()->highestRiskLevel();
        $suspiciousUrls      = $this->findSuspiciousUrls($email);
        $senderMismatch      = $this->detectSenderMismatch($email);

        $overallRisk = $this->computeOverallRisk(
            $phishingConfidence,
            $hasDangerousAttach,
            $attachmentRisk,
            $suspiciousUrls,
            $senderMismatch
        );

        return [
            'is_phishing'             => $phishingConfidence >= 50,
            'phishing_confidence'     => $phishingConfidence,
            'has_dangerous_attachment'=> $hasDangerousAttach,
            'attachment_risk'         => $attachmentRisk,
            'suspicious_urls'         => $suspiciousUrls,
            'phishing_indicators'     => $phishingIndicators,
            'sender_mismatch'         => $senderMismatch,
            'overall_risk'            => $overallRisk,
        ];
    }

    /**
     * @return string[]
     */
    private function detectPhishingIndicators(Email $email): array
    {
        $indicators = [];
        $full = strtolower($email->subject() . ' ' . $email->body() . ' ' . strip_tags($email->htmlBody()));

        foreach (self::PHISHING_INDICATORS as $indicator) {
            if (str_contains($full, $indicator)) {
                $indicators[] = $indicator;
            }
        }

        if ($this->detectSenderMismatch($email)) {
            $indicators[] = 'Sender name/domain mismatch (display name spoofing)';
        }

        // Display name impersonates known brands
        $sender = $email->sender();
        $senderName = strtolower($sender['name'] ?? '');
        $senderEmail = strtolower($sender['email'] ?? '');
        $senderDomain = StringHelper::domainFromEmail($senderEmail);
        $knownBrands = ['paypal', 'amazon', 'apple', 'microsoft', 'google', 'netflix', 'facebook', 'instagram'];

        foreach ($knownBrands as $brand) {
            if (str_contains($senderName, $brand) && !str_contains($senderDomain, $brand)) {
                $indicators[] = "Display name impersonates '{$brand}' but email domain does not match";
                break;
            }
        }

        return $indicators;
    }

    /**
     * @param string[] $indicators
     */
    private function phishingScore(Email $email, array $indicators): int
    {
        $score = 0;
        $score += min(count($indicators) * 20, 60);

        $urls = StringHelper::extractUrls($email->body() . ' ' . strip_tags($email->htmlBody()));
        foreach ($urls as $url) {
            if ($this->isShortenedUrl($url)) {
                $score += 10;
                break;
            }
        }

        if ($this->detectSenderMismatch($email)) {
            $score += 20;
        }

        return min($score, 100);
    }

    /**
     * @return string[]
     */
    private function findSuspiciousUrls(Email $email): array
    {
        $body = $email->body() . ' ' . strip_tags($email->htmlBody());
        $urls = StringHelper::extractUrls($body);

        return array_values(array_filter($urls, fn (string $url) => $this->isShortenedUrl($url)));
    }

    private function isShortenedUrl(string $url): bool
    {
        foreach (self::SHORTENERS as $shortener) {
            if (str_contains($url, $shortener)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if display name and email domain don't match (common phishing tactic).
     */
    private function detectSenderMismatch(Email $email): bool
    {
        $sender = $email->sender();
        $name   = strtolower($sender['name'] ?? '');
        $addr   = strtolower($sender['email'] ?? '');

        if ($name === '' || $addr === '') {
            return false;
        }

        // Name looks like an email address but doesn't match actual sender
        if (str_contains($name, '@')) {
            $nameDomain  = StringHelper::domainFromEmail($name);
            $emailDomain = StringHelper::domainFromEmail($addr);

            return $nameDomain !== $emailDomain;
        }

        return false;
    }

    private function computeOverallRisk(
        int    $phishingConfidence,
        bool   $hasDangerousAttachment,
        string $attachmentRisk,
        array  $suspiciousUrls,
        bool   $senderMismatch
    ): string {
        if ($phishingConfidence >= 70 || $hasDangerousAttachment && $attachmentRisk === 'critical') {
            return 'critical';
        }
        if ($phishingConfidence >= 50 || $attachmentRisk === 'high') {
            return 'high';
        }
        if ($phishingConfidence >= 30 || $attachmentRisk === 'medium' || $suspiciousUrls !== []) {
            return 'medium';
        }
        if ($senderMismatch || $attachmentRisk === 'low') {
            return 'low';
        }

        return 'safe';
    }
}

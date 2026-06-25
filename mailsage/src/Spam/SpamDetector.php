<?php

declare(strict_types=1);

namespace MailSage\Spam;

use MailSage\Contracts\DetectorInterface;
use MailSage\Helpers\StringHelper;
use MailSage\Models\Email;

class SpamDetector implements DetectorInterface
{
    private const SPAM_KEYWORDS = [
        'win a prize', 'you have won', 'congratulations you', 'click here now',
        'act now', 'limited time offer', 'free gift', 'earn money fast',
        'make money online', 'work from home', 'guaranteed income',
        'no credit check', 'risk free', 'double your income', 'increase sales',
        'weight loss', 'lose weight fast', 'diet pills', 'herbal remedy',
        'casino', 'online pharmacy', 'cheap meds', 'lowest price guarantee',
        'unsubscribe', 'opt out', 'remove from list', 'click to unsubscribe',
        'nigerian prince', 'transfer funds', 'million dollars',
        'urgent business', 'strictly confidential', 'reply urgently',
    ];

    private const PHISHING_KEYWORDS = [
        'verify your account', 'confirm your details', 'update your password',
        'your account has been suspended', 'unusual activity detected',
        'click to verify', 'validate your email', 'your account will be closed',
        'security alert', 'unauthorized access', 'confirm your identity',
        'your paypal', 'your amazon', 'your apple id', 'your bank account',
        'enter your credentials', 'dear valued customer', 'dear account holder',
    ];

    private const SUSPICIOUS_DOMAINS = [
        'mailinator.com', 'guerrillamail.com', 'throwam.com',
        'tempmail.com', 'trashmail.com', '10minutemail.com',
        'dispostable.com', 'yopmail.com', 'spamgourmet.com',
        'sharklasers.com', 'guerrillamailblock.com', 'grr.la',
    ];

    /**
     * Detect whether the email is spam.
     */
    public function detect(Email $email): bool
    {
        return $this->score($email) >= 50;
    }

    public function confidence(Email $email): int
    {
        return $this->score($email);
    }

    /**
     * Compute a spam score 0-100.
     */
    public function score(Email $email): int
    {
        $score = 0;
        $subject = $email->subject();
        $body    = $email->body() . ' ' . strip_tags($email->htmlBody());
        $full    = $subject . ' ' . $body;

        // Spam keywords in subject (high weight)
        $subjectHits = StringHelper::countKeywordHits($subject, self::SPAM_KEYWORDS);
        $score += min($subjectHits * 15, 30);

        // Spam keywords in body
        $bodyHits = StringHelper::countKeywordHits($body, self::SPAM_KEYWORDS);
        $score += min($bodyHits * 5, 20);

        // Phishing keywords
        $phishHits = StringHelper::countKeywordHits($full, self::PHISHING_KEYWORDS);
        $score += min($phishHits * 8, 24);

        // Excessive ALL CAPS
        $capsCount = StringHelper::capsWordCount($full);
        if ($capsCount > 10) {
            $score += 10;
        } elseif ($capsCount > 5) {
            $score += 5;
        }

        // Excessive links
        $urls = StringHelper::extractUrls($full);
        if (count($urls) > 10) {
            $score += 10;
        } elseif (count($urls) > 5) {
            $score += 5;
        }

        // Shortened URLs
        foreach ($urls as $url) {
            if (preg_match('/https?:\/\/(?:bit\.ly|tinyurl\.com|t\.co|goo\.gl|ow\.ly|is\.gd|buff\.ly|rb\.gy)/i', $url)) {
                $score += 5;
                break;
            }
        }

        // Suspicious sender domain
        $sender = $email->sender();
        if (isset($sender['email'])) {
            $domain = StringHelper::domainFromEmail($sender['email']);
            if (in_array($domain, self::SUSPICIOUS_DOMAINS, true)) {
                $score += 15;
            }
        }

        // Subject is all caps
        if ($subject !== '' && strtoupper($subject) === $subject && strlen($subject) > 5) {
            $score += 8;
        }

        // No plain text body (suspicious)
        if (trim($email->body()) === '' && trim($email->htmlBody()) !== '') {
            $score += 3;
        }

        // Exclamation marks
        $exclamations = substr_count($subject . $body, '!');
        if ($exclamations > 5) {
            $score += 5;
        }

        return min($score, 100);
    }

    /**
     * Get a full breakdown of spam indicators.
     *
     * @return array{score: int, is_spam: bool, indicators: string[]}
     */
    public function analyze(Email $email): array
    {
        $score      = $this->score($email);
        $indicators = $this->indicators($email);

        return [
            'score'      => $score,
            'is_spam'    => $score >= 50,
            'indicators' => $indicators,
        ];
    }

    /**
     * @return string[]
     */
    private function indicators(Email $email): array
    {
        $flags   = [];
        $subject = $email->subject();
        $body    = $email->body() . ' ' . strip_tags($email->htmlBody());
        $full    = $subject . ' ' . $body;

        if (StringHelper::countKeywordHits($full, self::SPAM_KEYWORDS) > 0) {
            $flags[] = 'Contains known spam keywords';
        }
        if (StringHelper::countKeywordHits($full, self::PHISHING_KEYWORDS) > 0) {
            $flags[] = 'Contains phishing-related language';
        }
        if (StringHelper::capsWordCount($full) > 5) {
            $flags[] = 'Excessive ALL CAPS usage';
        }
        if (count(StringHelper::extractUrls($full)) > 5) {
            $flags[] = 'Excessive number of hyperlinks';
        }

        $sender = $email->sender();
        if (isset($sender['email'])) {
            $domain = StringHelper::domainFromEmail($sender['email']);
            if (in_array($domain, self::SUSPICIOUS_DOMAINS, true)) {
                $flags[] = 'Sender uses a known disposable email domain';
            }
        }

        if ($subject !== '' && strtoupper($subject) === $subject && strlen($subject) > 5) {
            $flags[] = 'Subject line is written in ALL CAPS';
        }

        return $flags;
    }
}

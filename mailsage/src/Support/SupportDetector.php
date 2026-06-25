<?php

declare(strict_types=1);

namespace MailSage\Support;

use MailSage\Contracts\DetectorInterface;
use MailSage\Helpers\StringHelper;
use MailSage\Models\Email;

class SupportDetector implements DetectorInterface
{
    private const SUPPORT_KEYWORDS = [
        'help', 'support', 'issue', 'problem', 'bug', 'error', 'broken',
        'not working', 'cannot login', 'can\'t login', 'forgot password',
        'reset password', 'account locked', 'refund', 'cancel subscription',
        'billing issue', 'charge dispute', 'complaint', 'feedback',
        'ticket', 'request', 'technical issue', 'how do i', 'unable to',
    ];

    private const CATEGORY_KEYWORDS = [
        'bug_report'     => ['bug', 'crash', 'error', 'broken', 'not working', 'glitch', '500', '404'],
        'login_issue'    => ['login', 'password', 'sign in', 'authentication', 'access denied', 'locked out', '2fa'],
        'refund_request' => ['refund', 'money back', 'charge', 'billing', 'overcharged', 'dispute', 'cancel'],
        'complaint'      => ['complaint', 'unacceptable', 'frustrated', 'disappointed', 'terrible', 'worst'],
        'technical'      => ['technical', 'integration', 'api', 'webhook', 'setup', 'configuration', 'install'],
    ];

    public function detect(Email $email): bool
    {
        return $this->confidence($email) >= 30;
    }

    public function confidence(Email $email): int
    {
        $score = 0;
        $full  = $email->subject() . ' ' . $email->body() . ' ' . strip_tags($email->htmlBody());

        $hits = StringHelper::countKeywordHits($full, self::SUPPORT_KEYWORDS);
        $score += min($hits * 10, 60);

        $subject = strtolower($email->subject());
        foreach (['support', 'help', 'issue', 'problem', 'ticket'] as $kw) {
            if (str_contains($subject, $kw)) {
                $score += 20;
                break;
            }
        }

        return min($score, 100);
    }

    /**
     * Classify the support request into a specific sub-category.
     */
    public function subCategory(Email $email): string
    {
        $full = strtolower($email->subject() . ' ' . $email->body());

        foreach (self::CATEGORY_KEYWORDS as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($full, $keyword)) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    /**
     * @return array{is_support: bool, confidence: int, sub_category: string}
     */
    public function analyze(Email $email): array
    {
        $confidence = $this->confidence($email);

        return [
            'is_support'   => $confidence >= 30,
            'confidence'   => $confidence,
            'sub_category' => $confidence >= 30 ? $this->subCategory($email) : 'none',
        ];
    }
}

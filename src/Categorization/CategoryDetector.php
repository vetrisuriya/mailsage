<?php

declare(strict_types=1);

namespace MailSage\Categorization;

use MailSage\Helpers\StringHelper;
use MailSage\Invoice\InvoiceDetector;
use MailSage\Lead\LeadDetector;
use MailSage\Models\Email;
use MailSage\Order\OrderDetector;
use MailSage\Spam\SpamDetector;
use MailSage\Support\SupportDetector;

class CategoryDetector
{
    private const BUILTIN_KEYWORDS = [
        'invoice'         => ['invoice', 'bill', 'receipt', 'payment due', 'tax invoice', 'proforma'],
        'order'           => ['order confirmation', 'order #', 'your order', 'tracking', 'shipment', 'shipped'],
        'support'         => ['support', 'help', 'issue', 'problem', 'ticket', 'bug', 'error'],
        'sales'           => ['sales', 'quote', 'proposal', 'pricing', 'offer', 'discount', 'deal'],
        'marketing'       => ['newsletter', 'promotion', 'offer', 'special offer', 'sale', 'campaign', 'subscribe'],
        'feedback'        => ['feedback', 'review', 'survey', 'testimonial', 'rating', 'opinion'],
        'job_application' => ['job application', 'resume', 'cv', 'cover letter', 'applying for', 'position'],
        'partnership'     => ['partnership', 'collaboration', 'joint venture', 'affiliation', 'co-marketing'],
        'spam'            => ['win a prize', 'click here', 'act now', 'free gift', 'limited offer'],
        'general'         => [],
    ];

    private SpamDetector    $spamDetector;
    private InvoiceDetector $invoiceDetector;
    private OrderDetector   $orderDetector;
    private LeadDetector    $leadDetector;
    private SupportDetector $supportDetector;

    public function __construct()
    {
        $this->spamDetector    = new SpamDetector();
        $this->invoiceDetector = new InvoiceDetector();
        $this->orderDetector   = new OrderDetector();
        $this->leadDetector    = new LeadDetector();
        $this->supportDetector = new SupportDetector();
    }

    /**
     * Detect the primary category of the email.
     */
    public function detect(Email $email): string
    {
        $scores = $this->scoreAll($email);
        arsort($scores);

        return array_key_first($scores) ?? 'general';
    }

    /**
     * Return the confidence score for the detected category (0-100).
     */
    public function confidence(Email $email): int
    {
        $scores = $this->scoreAll($email);
        if ($scores === []) {
            return 0;
        }
        arsort($scores);

        return (int) (array_values($scores)[0] ?? 0);
    }

    /**
     * Score all categories and return a map of category => score.
     *
     * @return array<string, int>
     */
    public function scoreAll(Email $email): array
    {
        $full   = strtolower($email->subject() . ' ' . $email->body() . ' ' . strip_tags($email->htmlBody()));
        $scores = [];

        // Dedicated detectors (highest accuracy)
        $spamScore    = $this->spamDetector->confidence($email);
        $invoiceScore = $this->invoiceDetector->confidence($email);
        $orderScore   = $this->orderDetector->confidence($email);
        $leadScore    = $this->leadDetector->confidence($email);
        $supportScore = $this->supportDetector->confidence($email);

        if ($spamScore >= 50) {
            $scores['spam'] = $spamScore;
        }
        if ($invoiceScore >= 40) {
            $scores['invoice'] = $invoiceScore;
        }
        if ($orderScore >= 40) {
            $scores['order'] = $orderScore;
        }
        if ($leadScore >= 35) {
            $scores['sales'] = $leadScore;
        }
        if ($supportScore >= 30) {
            $scores['support'] = $supportScore;
        }

        // Keyword-based scoring for remaining categories
        foreach (self::BUILTIN_KEYWORDS as $category => $keywords) {
            if (isset($scores[$category]) || $keywords === []) {
                continue;
            }
            $hits = StringHelper::countKeywordHits($full, $keywords);
            if ($hits > 0) {
                $scores[$category] = min($hits * 15, 80);
            }
        }

        // Custom categories
        foreach (Category::getCustomCategories() as $category => $keywords) {
            $hits = StringHelper::countKeywordHits($full, $keywords);
            if ($hits > 0) {
                $scores[$category] = min($hits * 15, 80);
            }
        }

        // Fallback to general
        if ($scores === []) {
            $scores['general'] = 10;
        }

        return $scores;
    }
}

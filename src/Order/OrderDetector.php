<?php

declare(strict_types=1);

namespace MailSage\Order;

use MailSage\Contracts\DetectorInterface;
use MailSage\Helpers\RegexPatterns;
use MailSage\Helpers\StringHelper;
use MailSage\Models\Email;

class OrderDetector implements DetectorInterface
{
    private const SUBJECT_KEYWORDS = [
        'order confirmation', 'your order', 'order received', 'order #',
        'order number', 'thanks for your order', 'thank you for your order',
        'order placed', 'purchase confirmation', 'order summary',
        'shipment confirmation', 'your receipt', 'order shipped',
    ];

    private const BODY_KEYWORDS = [
        'order number', 'order #', 'order total', 'items ordered',
        'estimated delivery', 'shipping address', 'billing address',
        'tracking number', 'you ordered', 'order confirmation',
    ];

    private const PLATFORMS = [
        'woocommerce' => ['woocommerce', 'woo', 'wordpress'],
        'shopify'     => ['shopify', 'myshopify.com'],
        'magento'     => ['magento', 'magentocommerce'],
        'prestashop'  => ['prestashop'],
        'bigcommerce' => ['bigcommerce'],
        'amazon'      => ['amazon.com', 'amazon.co'],
        'ebay'        => ['ebay.com', 'ebay.co'],
        'etsy'        => ['etsy.com'],
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

        foreach (self::SUBJECT_KEYWORDS as $kw) {
            if (str_contains($subject, $kw)) {
                $score += 30;
                break;
            }
        }

        $bodyHits = StringHelper::countKeywordHits($body, self::BODY_KEYWORDS);
        $score += min($bodyHits * 8, 35);

        // Has order number
        if (preg_match(RegexPatterns::ORDER_NUMBER, $email->subject() . ' ' . $email->body())) {
            $score += 15;
        }

        // Has amount
        if (preg_match(RegexPatterns::ORDER_AMOUNT, $email->body())) {
            $score += 10;
        }

        return min($score, 100);
    }

    public function extract(Email $email): Order
    {
        $text = $email->subject() . "\n" . $email->body() . "\n" . strip_tags($email->htmlBody());

        return new Order(
            number:   $this->extractNumber($text),
            customer: $this->extractCustomer($email),
            amount:   $this->extractAmount($text),
            currency: $this->extractCurrency($text),
            platform: $this->detectPlatform($email),
        );
    }

    private function extractNumber(string $text): ?string
    {
        if (preg_match(RegexPatterns::ORDER_WOOCOMMERCE, $text, $m)) {
            return trim($m[1]);
        }
        if (preg_match(RegexPatterns::ORDER_SHOPIFY, $text, $m)) {
            return trim($m[1]);
        }
        if (preg_match(RegexPatterns::ORDER_NUMBER, $text, $m)) {
            return strtoupper(trim($m[1]));
        }

        return null;
    }

    private function extractCustomer(Email $email): ?string
    {
        $recipient = $email->recipient();
        if (!empty($recipient[0]['name'])) {
            return $recipient[0]['name'];
        }

        return null;
    }

    private function extractAmount(string $text): ?float
    {
        if (preg_match(RegexPatterns::ORDER_AMOUNT, $text, $m)) {
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

        $symbols = ['$' => 'USD', '€' => 'EUR', '£' => 'GBP', '₹' => 'INR'];
        foreach ($symbols as $sym => $code) {
            if (str_contains($text, $sym)) {
                return $code;
            }
        }

        return null;
    }

    private function detectPlatform(Email $email): ?string
    {
        $senderDomain = '';
        $sender = $email->sender();
        if (!empty($sender['email'])) {
            $senderDomain = StringHelper::domainFromEmail($sender['email']);
        }

        $fullText = strtolower(
            $senderDomain . ' ' .
            $email->subject() . ' ' .
            $email->body()
        );

        foreach (self::PLATFORMS as $platform => $indicators) {
            foreach ($indicators as $indicator) {
                if (str_contains($fullText, $indicator)) {
                    return $platform;
                }
            }
        }

        return 'generic';
    }
}

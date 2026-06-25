<?php

declare(strict_types=1);

namespace MailSage\Helpers;

class RegexPatterns
{
    // Invoice patterns
    public const INVOICE_NUMBER = '/(?:invoice|inv|bill|receipt)\s*[:#\-]?\s*([A-Z0-9\-]{3,20})/i';
    public const INVOICE_AMOUNT = '/(?:total|amount|subtotal|due|balance)\s*[:\-]?\s*(?:USD|GBP|EUR|INR|AUD|CAD|[$€£₹])\s*([\d,]+\.?\d{0,2})/i';
    public const INVOICE_AMOUNT_ALT = '/(?:USD|GBP|EUR|INR|AUD|CAD|[$€£₹])\s*([\d,]+\.?\d{0,2})/i';
    public const INVOICE_DATE = '/(?:invoice\s*date|date\s*of\s*invoice|bill\s*date|issued?\s*(?:on|date)?)\s*[:\-]?\s*(\d{1,2}[\-\/]\d{1,2}[\-\/]\d{2,4}|\w+ \d{1,2},?\s*\d{4})/i';

    // Order patterns
    public const ORDER_NUMBER = '/(?:order|ord|confirmation|purchase)\s*[:#\-]?\s*([A-Z0-9\-]{4,25})/i';
    public const ORDER_WOOCOMMERCE = '/(?:order\s*#|your\s*order\s*number\s*is)\s*([0-9]{4,10})/i';
    public const ORDER_SHOPIFY = '/(?:order\s*#|shopify\s*order)\s*([0-9]{4,12})/i';
    public const ORDER_AMOUNT = '/(?:order\s*total|total|subtotal|amount\s*charged)\s*[:\-]?\s*(?:USD|GBP|EUR|INR|AUD|CAD|[$€£₹])\s*([\d,]+\.?\d{0,2})/i';

    // Currency patterns
    public const CURRENCY_CODE = '/\b(USD|GBP|EUR|INR|AUD|CAD|JPY|CHF|CNY|SGD)\b/';
    public const CURRENCY_SYMBOL = '/[$€£₹¥]/';

    // Date patterns
    public const GENERIC_DATE = '/\b(\d{1,2}[\-\/\.]\d{1,2}[\-\/\.]\d{2,4}|\w+ \d{1,2},?\s*\d{4}|\d{4}[\-\/]\d{1,2}[\-\/]\d{1,2})\b/';

    // Phone patterns
    public const PHONE = '/(?:\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/';

    // URL patterns
    public const URL = '/https?:\/\/[^\s<>"\')\]]+/i';
    public const SHORTENED_URL = '/https?:\/\/(?:bit\.ly|tinyurl\.com|t\.co|goo\.gl|ow\.ly|is\.gd|buff\.ly|adf\.ly|short\.link|rb\.gy|cutt\.ly)\/[^\s]+/i';

    // Email patterns
    public const EMAIL_ADDRESS = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';

    // Lead patterns
    public const LEAD_NAME_FIELD = '/(?:name|full\s*name|your\s*name)\s*[:\-]\s*([A-Za-z ]{2,50})/i';
    public const LEAD_COMPANY = '/(?:company|organization|organisation|business)\s*[:\-]\s*([A-Za-z0-9 &.,]{2,60})/i';
    public const LEAD_PHONE = '/(?:phone|tel|mobile|cell|contact\s*number)\s*[:\-]\s*([\+\d\s\-\(\)]{7,20})/i';
}

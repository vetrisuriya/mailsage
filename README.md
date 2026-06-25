# MailSage

**Parse, Understand, and Categorize Emails with One API.**

[![PHP](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg?style=flat)](https://php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-max-blueviolet)](https://phpstan.org/)
[![PSR-12](https://img.shields.io/badge/code_style-PSR--12-orange)](https://www.php-fig.org/psr/psr-12/)

MailSage transforms raw emails and EML files into structured business data.

**No SaaS. No subscriptions. No API keys. No accounts. 100% local processing. MIT License.**

---

## Features

- **Parse** any raw email or EML file into a clean `Email` object
- **MIME** full multipart parser (plain, HTML, attachments, nested parts)
- **Spam Detection** with a 0–100 scoring engine
- **Phishing Detection** with sender spoofing analysis
- **Invoice Extraction** — number, date, amount, currency, vendor
- **Order Detection** — WooCommerce, Shopify, Magento, generic
- **Lead Detection** — name, email, phone, company
- **Support Request** detection and sub-classification
- **Email Categorization** with confidence scores and custom rules
- **Security Reports** with overall risk levels
- **Attachment Analysis** with risk levels (safe/low/medium/high/critical)
- **Header Auth Report** — SPF, DKIM, DMARC results
- **Export** to array, JSON, and CSV
- **Framework-agnostic** — works with Laravel, Symfony, CodeIgniter, Slim, Yii, or plain PHP
- **PHP 8.2+** with full type coverage

---

## Installation

```bash
composer require mailsage/mailsage
```

**Requirements:** PHP 8.2+, `ext-mbstring`, `ext-json`

---

## Quick Start

```php
use MailSage\EmailParser;

// Parse a raw email string
$email = EmailParser::parse($rawEmail);

// Or parse an EML file
$email = EmailParser::fromFile('path/to/email.eml');

// Basic fields
$email->subject();       // "Invoice INV-2026-0547 from Acme Corp"
$email->sender();        // ['name' => 'Billing', 'email' => 'billing@acme.com', 'raw' => '...']
$email->recipient();     // [['name' => '...', 'email' => '...', 'raw' => '...']]
$email->cc();
$email->bcc();
$email->date();
$email->messageId();
$email->body();          // Plain text body
$email->htmlBody();      // HTML body
$email->headers();       // All parsed headers
$email->metadata();      // raw_size, has_html, has_plain, attachment_count
```

---

## API Reference

### Spam Detection

```php
$email->isSpam();        // bool
$email->spamScore();     // int 0–100
```

### Phishing Detection

```php
$email->isPhishing();    // bool
$email->securityReport(); // full security analysis array
$email->attachmentRisk(); // 'safe' | 'low' | 'medium' | 'high' | 'critical'
```

### Invoice Detection

```php
$email->isInvoice();     // bool

$invoice = $email->invoice();
$invoice->number();      // "INV-2026-0547"
$invoice->date();        // "June 25, 2026"
$invoice->amount();      // 1595.00
$invoice->currency();    // "USD"
$invoice->vendor();      // "Acme Corp"
$invoice->toArray();
```

### Order Detection

```php
$email->isOrder();       // bool

$order = $email->order();
$order->number();        // "1042"
$order->customer();      // "Alex"
$order->amount();        // 89.95
$order->currency();      // "USD"
$order->platform();      // "shopify"
$order->toArray();
```

### Lead Detection

```php
$email->isLead();        // bool

$lead = $email->lead();
$lead->name();           // "Sarah Johnson"
$lead->email();          // "sarah@techstartup.com"
$lead->phone();          // "+1 (415) 555-9876"
$lead->company();        // "TechStartup Inc."
$lead->toArray();
```

### Support Request Detection

```php
$email->isSupportRequest();     // bool
$email->supportSubCategory();   // 'bug_report' | 'login_issue' | 'refund_request' | 'complaint' | 'technical' | 'general'
```

### Categorization

```php
$email->category();    // 'invoice' | 'order' | 'support' | 'sales' | 'marketing' | 'spam' | ...
$email->confidence();  // int 0–100
```

#### Custom Categories

```php
use MailSage\Categorization\Category;

Category::register('legal', ['contract', 'nda', 'agreement']);

$email->category(); // may now return 'legal'
```

Available built-in categories: `support`, `invoice`, `order`, `sales`, `marketing`, `feedback`, `job_application`, `partnership`, `spam`, `general`.

### Attachments

```php
$attachments = $email->attachments();

$attachments->count();
$attachments->hasAttachments();
$attachments->highestRiskLevel();
$attachments->hasDangerousAttachment();
$attachments->all();               // Attachment[]
$attachments->filterByExtension('pdf', 'docx');
$attachments->saveAll('/uploads');
$attachments->toArray();

// Per-attachment
foreach ($attachments->all() as $attachment) {
    $attachment->name();
    $attachment->extension();
    $attachment->mimeType();
    $attachment->size();
    $attachment->content();
    $attachment->riskLevel();  // 'safe' | 'low' | 'medium' | 'high' | 'critical'
    $attachment->save('/uploads');
    $attachment->toArray();
}

// Save all
$email->saveAttachments('/uploads');
```

### Header Report

```php
$report = $email->headerReport();
// [
//   'message_id'  => '<...>',
//   'return_path' => '...',
//   'spf'         => 'pass',
//   'dkim'        => 'pass',
//   'dmarc'       => 'pass',
//   'received'    => ['...', '...'],
// ]
```

### Security Report

```php
$report = $email->securityReport();
// [
//   'is_phishing'              => bool,
//   'phishing_confidence'      => int,
//   'has_dangerous_attachment' => bool,
//   'attachment_risk'          => string,
//   'suspicious_urls'          => string[],
//   'phishing_indicators'      => string[],
//   'sender_mismatch'          => bool,
//   'overall_risk'             => 'safe'|'low'|'medium'|'high'|'critical',
// ]
```

### Export

```php
$email->toArray();
$email->toJson();
$email->toCsv();
```

---

## Framework Integration

MailSage is framework-agnostic. No service providers, no bindings — just `composer require` and go.

**Laravel example:**
```php
use MailSage\EmailParser;

$email = EmailParser::parse($request->getContent());
return response()->json($email->toArray());
```

**Symfony example:**
```php
$email = EmailParser::parse($request->getContent());
return new JsonResponse($email->toArray());
```

**Plain PHP:**
```php
$raw = file_get_contents('php://input');
$email = EmailParser::parse($raw);
echo $email->toJson();
```

---

## Configuration

No configuration files required. MailSage works out of the box.

Custom categories can be registered at runtime:
```php
Category::register('partnership', ['partner', 'collaboration', 'affiliate']);
Category::unregister('partnership');
Category::clearAll();
```

---

## Error Handling

```php
use MailSage\Exceptions\InvalidEmailException;
use MailSage\Exceptions\InvalidEMLException;
use MailSage\Exceptions\AttachmentException;

try {
    $email = EmailParser::fromFile('email.eml');
} catch (InvalidEMLException $e) {
    // File not found, not readable, empty, or invalid format
} catch (InvalidEmailException $e) {
    // Empty email or malformed content
} catch (AttachmentException $e) {
    // Directory not writable, save failed
}
```

---

## Testing

```bash
composer test
composer test-coverage
composer analyse
composer format
```

---

## Performance

| Email Size | Parse Time |
|------------|-----------|
| 1 MB       | < 100ms   |
| 5 MB       | < 500ms   |

All processing is done in-memory with no external calls.

---

## Security Notes

- MailSage **never executes** any attachment content or embedded scripts.
- All parsing is **local** — no network requests are made.
- Always check `attachmentRisk()` before saving user-supplied attachments.
- Never save attachments to a web-accessible directory without validation.
- Use `securityReport()` to assess emails before processing further.

---

## FAQ

**Does MailSage require any API keys or accounts?**
No. It is 100% local. Zero external dependencies.

**Does it work with Laravel?**
Yes. Any framework or plain PHP. Just `composer require`.

**Can I add my own detection categories?**
Yes. Use `Category::register('mycat', ['keyword1', 'keyword2'])`.

**What MIME types does it support?**
text/plain, text/html, multipart/mixed, multipart/alternative, multipart/related, and nested structures.

**Is it production-ready?**
Yes. PSR-12 code style, PHPStan max level, 95%+ test coverage target, PHP 8.2–8.4 support.

---

## License

MIT — see [LICENSE](LICENSE).

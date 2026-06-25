# MailSage API Reference

## EmailParser

The main entry point for all parsing operations.

### Static Methods

#### `EmailParser::parse(string $rawEmail): Email`
Parse a raw email string. Throws `InvalidEmailException` if the string is empty.

#### `EmailParser::fromFile(string $filePath): Email`
Parse an EML file from disk. Throws `InvalidEMLException` if the file is missing,
unreadable, empty, or not a valid email format.

---

## Email Model

All parsing returns an `Email` instance with the following methods:

### Basic Fields

| Method | Return | Description |
|--------|--------|-------------|
| `subject()` | `string` | Decoded subject line |
| `sender()` | `array` | `{name, email, raw}` |
| `recipient()` | `array[]` | List of `{name, email, raw}` |
| `cc()` | `array[]` | CC recipients |
| `bcc()` | `array[]` | BCC recipients |
| `date()` | `string` | Raw date header value |
| `messageId()` | `string` | Message-ID header |
| `body()` | `string` | Decoded plain text body |
| `htmlBody()` | `string` | Decoded HTML body |
| `headers()` | `array` | All headers (key: lowercase name) |
| `header(string $name)` | `string\|array\|null` | Single header value |
| `metadata()` | `array` | raw_size, has_html, has_plain, attachment_count |

### Intelligence Methods

| Method | Return | Description |
|--------|--------|-------------|
| `isSpam()` | `bool` | Spam score ≥ 50 |
| `spamScore()` | `int` | Spam score 0–100 |
| `isPhishing()` | `bool` | Phishing confidence ≥ 50 |
| `isInvoice()` | `bool` | Invoice detected |
| `invoice()` | `Invoice` | Extracted invoice data |
| `isOrder()` | `bool` | Order detected |
| `order()` | `Order` | Extracted order data |
| `isLead()` | `bool` | Lead detected |
| `lead()` | `Lead` | Extracted lead data |
| `isSupportRequest()` | `bool` | Support request detected |
| `supportSubCategory()` | `string` | bug_report, login_issue, refund_request, complaint, technical, general |
| `category()` | `string` | Primary category |
| `confidence()` | `int` | Category confidence 0–100 |

### Reports

| Method | Return | Description |
|--------|--------|-------------|
| `headerReport()` | `array` | SPF, DKIM, DMARC, Return-Path, Received |
| `securityReport()` | `array` | Full security analysis |
| `attachmentRisk()` | `string` | Highest attachment risk level |

### Attachments

| Method | Return | Description |
|--------|--------|-------------|
| `attachments()` | `AttachmentManager` | Attachment manager instance |
| `saveAttachments(string $dir)` | `string[]` | Save all attachments, return paths |

### Export

| Method | Return | Description |
|--------|--------|-------------|
| `toArray()` | `array` | All email data as PHP array |
| `toJson(int $flags)` | `string` | JSON representation |
| `toCsv()` | `string` | CSV representation (flat, single row) |

---

## AttachmentManager

Returned by `$email->attachments()`.

| Method | Return | Description |
|--------|--------|-------------|
| `all()` | `Attachment[]` | All attachment objects |
| `count()` | `int` | Number of attachments |
| `hasAttachments()` | `bool` | Whether any attachments exist |
| `highestRiskLevel()` | `string` | Highest risk across all attachments |
| `hasDangerousAttachment()` | `bool` | Any high/critical risk attachment |
| `filterByExtension(string ...$ext)` | `Attachment[]` | Filter by extension |
| `saveAll(string $dir)` | `string[]` | Save all, return paths |
| `toArray()` | `array[]` | All attachments as arrays |

---

## Attachment

| Method | Return | Description |
|--------|--------|-------------|
| `name()` | `string` | Original filename |
| `extension()` | `string` | Lowercase file extension |
| `mimeType()` | `string` | MIME type |
| `size()` | `int` | Size in bytes |
| `content()` | `string` | Decoded binary content |
| `riskLevel()` | `string` | safe / low / medium / high / critical |
| `save(string $dir)` | `string` | Save to directory, return path |
| `toArray()` | `array` | Properties as array |

---

## Invoice

| Method | Return | Description |
|--------|--------|-------------|
| `number()` | `?string` | Invoice number |
| `date()` | `?string` | Invoice date |
| `amount()` | `?float` | Total amount |
| `currency()` | `?string` | Currency code (USD, EUR, GBP...) |
| `vendor()` | `?string` | Vendor name or domain |
| `toArray()` | `array` | All fields as array |

---

## Order

| Method | Return | Description |
|--------|--------|-------------|
| `number()` | `?string` | Order number |
| `customer()` | `?string` | Customer name |
| `amount()` | `?float` | Order total |
| `currency()` | `?string` | Currency code |
| `platform()` | `?string` | shopify, woocommerce, magento, amazon, generic... |
| `toArray()` | `array` | All fields as array |

---

## Lead

| Method | Return | Description |
|--------|--------|-------------|
| `name()` | `?string` | Full name |
| `email()` | `?string` | Email address |
| `phone()` | `?string` | Phone number |
| `company()` | `?string` | Company or organization |
| `toArray()` | `array` | All fields as array |

---

## Category (Registry)

```php
use MailSage\Categorization\Category;

Category::register(string $name, array $keywords): void
Category::unregister(string $name): void
Category::clearAll(): void
Category::has(string $name): bool
Category::getCustomCategories(): array
```

---

## Exceptions

| Exception | When Thrown |
|-----------|-------------|
| `InvalidEmailException` | Empty or unparseable email string |
| `InvalidEMLException` | File missing, unreadable, empty, or not valid EML |
| `AttachmentException` | Directory missing, not writable, or save failed |
| `ParserException` | MIME structure or encoding error |
| `SecurityException` | Security analysis error |
| `SpamDetectionException` | Spam analysis error |

---

## Spam Score Breakdown

| Indicator | Points |
|-----------|--------|
| Spam keywords in subject | Up to 30 |
| Spam keywords in body | Up to 20 |
| Phishing keywords | Up to 24 |
| ALL CAPS usage | Up to 10 |
| Excessive links | Up to 10 |
| Shortened URLs | 5 |
| Suspicious sender domain | 15 |
| ALL CAPS subject | 8 |
| Excessive exclamation marks | 5 |

Score of 50+ is considered spam.

---

## Risk Levels

| Level | Examples |
|-------|---------|
| `safe` | .png, .jpg, .gif, .txt, .csv |
| `low` | .pdf, .doc, .docx, .xls, .xlsx |
| `medium` | .docm, .xlsm, .pptm (macro-enabled) |
| `high` | .zip, .rar, .7z, .iso, .dll |
| `critical` | .exe, .bat, .cmd, .js, .ps1, .vbs, .scr |

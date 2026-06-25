# Changelog

All notable changes to MailSage will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-06-25

### Added
- Initial release of MailSage
- `EmailParser::parse()` - Parse raw email strings
- `EmailParser::fromFile()` - Parse EML files from disk
- Full MIME parser with multipart support (mixed, alternative, related)
- EML file reader supporting Outlook, Gmail, Thunderbird, Apple Mail exports
- Attachment extraction and risk analysis
- Spam detection engine with configurable scoring (0-100)
- Phishing detection with sender mismatch analysis
- Invoice detection and data extraction (number, date, amount, currency, vendor)
- Order detection supporting WooCommerce, Shopify, Magento, and generic platforms
- Lead detection with contact information extraction
- Support request detection with sub-category classification
- Email categorization engine with confidence scoring
- Custom category registration via `Category::register()`
- Security analysis report with overall risk level
- Header analysis including SPF, DKIM, DMARC results
- Export to array, JSON, and CSV
- Full PSR-4 autoloading, PSR-12 code style
- PHP 8.2, 8.3, 8.4 support
- Comprehensive PHPUnit test suite
- PHPStan level max analysis
- GitHub Actions CI/CD workflow

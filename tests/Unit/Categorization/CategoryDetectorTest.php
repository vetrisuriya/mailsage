<?php

declare(strict_types=1);

namespace MailSage\Tests\Unit\Categorization;

use MailSage\Categorization\Category;
use MailSage\Categorization\CategoryDetector;
use MailSage\EmailParser;
use PHPUnit\Framework\TestCase;

class CategoryDetectorTest extends TestCase
{
    private CategoryDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new CategoryDetector();
        Category::clearAll();
    }

    protected function tearDown(): void
    {
        Category::clearAll();
    }

    public function testCategorizesInvoiceEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $category = $this->detector->detect($email);
        $this->assertEquals('invoice', $category);
    }

    public function testCategorizesOrderEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/order.eml');
        $email = EmailParser::parse($raw);

        $category = $this->detector->detect($email);
        $this->assertEquals('order', $category);
    }

    public function testCategorizesSpamEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/spam.eml');
        $email = EmailParser::parse($raw);

        $category = $this->detector->detect($email);
        $this->assertEquals('spam', $category);
    }

    public function testCategorizeSupportEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/support.eml');
        $email = EmailParser::parse($raw);

        $category = $this->detector->detect($email);
        $this->assertEquals('support', $category);
    }

    public function testCustomCategoryRegistration(): void
    {
        Category::register('legal', ['contract', 'nda', 'agreement', 'legal notice']);

        $raw = "From: lawyer@firm.com\nTo: client@example.com\nSubject: NDA Agreement\n\nPlease review the attached NDA contract agreement.";
        $email = EmailParser::parse($raw);

        $scores = $this->detector->scoreAll($email);
        $this->assertArrayHasKey('legal', $scores);
    }

    public function testConfidenceIsWithinRange(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $confidence = $this->detector->confidence($email);
        $this->assertGreaterThanOrEqual(0, $confidence);
        $this->assertLessThanOrEqual(100, $confidence);
    }

    public function testScoreAllReturnsArray(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $scores = $this->detector->scoreAll($email);
        $this->assertIsArray($scores);
        $this->assertNotEmpty($scores);
    }
}

<?php

declare(strict_types=1);

namespace MailSage\Tests\Unit\Invoice;

use MailSage\EmailParser;
use MailSage\Invoice\Invoice;
use MailSage\Invoice\InvoiceDetector;
use PHPUnit\Framework\TestCase;

class InvoiceDetectorTest extends TestCase
{
    private InvoiceDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new InvoiceDetector();
    }

    public function testDetectsInvoiceEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $this->assertTrue($this->detector->detect($email));
    }

    public function testDoesNotDetectOrderAsInvoice(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);

        $this->assertFalse($this->detector->detect($email));
    }

    public function testExtractsInvoiceNumber(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $invoice = $this->detector->extract($email);
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertNotNull($invoice->number());
        $this->assertStringContainsString('INV', $invoice->number());
    }

    public function testExtractsInvoiceAmount(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $invoice = $this->detector->extract($email);
        $this->assertNotNull($invoice->amount());
        $this->assertGreaterThan(0, $invoice->amount());
    }

    public function testExtractsInvoiceCurrency(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $invoice = $this->detector->extract($email);
        $this->assertNotNull($invoice->currency());
        $this->assertEquals('USD', $invoice->currency());
    }

    public function testInvoiceToArray(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $invoice = $this->detector->extract($email);
        $arr = $invoice->toArray();

        $this->assertArrayHasKey('number', $arr);
        $this->assertArrayHasKey('date', $arr);
        $this->assertArrayHasKey('amount', $arr);
        $this->assertArrayHasKey('currency', $arr);
        $this->assertArrayHasKey('vendor', $arr);
    }

    public function testConfidenceIsWithinRange(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $confidence = $this->detector->confidence($email);
        $this->assertGreaterThanOrEqual(0, $confidence);
        $this->assertLessThanOrEqual(100, $confidence);
    }
}

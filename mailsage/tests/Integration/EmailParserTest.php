<?php

declare(strict_types=1);

namespace MailSage\Tests\Integration;

use MailSage\EmailParser;
use MailSage\Exceptions\InvalidEmailException;
use MailSage\Exceptions\InvalidEMLException;
use MailSage\Models\Email;
use PHPUnit\Framework\TestCase;

class EmailParserTest extends TestCase
{
    public function testParseReturnsEmailObject(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);

        $this->assertInstanceOf(Email::class, $email);
    }

    public function testParsesSenderCorrectly(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);

        $sender = $email->sender();
        $this->assertEquals('John Smith', $sender['name']);
        $this->assertEquals('john.smith@example.com', $sender['email']);
    }

    public function testParsesSubjectCorrectly(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);

        $this->assertStringContainsString('Q2 Milestones', $email->subject());
    }

    public function testParsesRecipientCorrectly(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);

        $recipients = $email->recipient();
        $this->assertNotEmpty($recipients);
        $this->assertEquals('jane.doe@company.com', $recipients[0]['email']);
    }

    public function testParsesCcCorrectly(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);

        $cc = $email->cc();
        $this->assertNotEmpty($cc);
        $this->assertEquals('manager@company.com', $cc[0]['email']);
    }

    public function testParsesBodyCorrectly(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);

        $this->assertStringContainsString('Q2 milestones', $email->body());
    }

    public function testFromFileParsesEMLFile(): void
    {
        $email = EmailParser::fromFile(__DIR__ . '/../../fixtures/sample.eml');
        $this->assertInstanceOf(Email::class, $email);
    }

    public function testFromFileThrowsOnMissingFile(): void
    {
        $this->expectException(InvalidEMLException::class);
        EmailParser::fromFile('/nonexistent/path/file.eml');
    }

    public function testParseThrowsOnEmptyEmail(): void
    {
        $this->expectException(InvalidEmailException::class);
        EmailParser::parse('');
    }

    public function testToArrayContainsAllKeys(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);
        $arr = $email->toArray();

        $this->assertArrayHasKey('subject', $arr);
        $this->assertArrayHasKey('sender', $arr);
        $this->assertArrayHasKey('recipient', $arr);
        $this->assertArrayHasKey('cc', $arr);
        $this->assertArrayHasKey('bcc', $arr);
        $this->assertArrayHasKey('date', $arr);
        $this->assertArrayHasKey('message_id', $arr);
        $this->assertArrayHasKey('body', $arr);
        $this->assertArrayHasKey('html_body', $arr);
        $this->assertArrayHasKey('attachments', $arr);
        $this->assertArrayHasKey('headers', $arr);
    }

    public function testToJsonIsValidJson(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);
        $json = $email->toJson();

        $this->assertJson($json);
    }

    public function testToCsvIsNonEmpty(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);
        $csv = $email->toCsv();

        $this->assertNotEmpty($csv);
        $this->assertStringContainsString('subject', $csv);
    }

    public function testHeaderReportStructure(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);
        $report = $email->headerReport();

        $this->assertArrayHasKey('message_id', $report);
        $this->assertArrayHasKey('return_path', $report);
        $this->assertArrayHasKey('spf', $report);
        $this->assertArrayHasKey('dkim', $report);
        $this->assertArrayHasKey('dmarc', $report);
    }

    public function testInvoiceEmailDetectionAndExtraction(): void
    {
        $email = EmailParser::fromFile(__DIR__ . '/../../fixtures/invoice.eml');

        $this->assertTrue($email->isInvoice());
        $invoice = $email->invoice();
        $this->assertNotNull($invoice->number());
    }

    public function testOrderEmailDetectionAndExtraction(): void
    {
        $email = EmailParser::fromFile(__DIR__ . '/../../fixtures/order.eml');

        $this->assertTrue($email->isOrder());
        $order = $email->order();
        $this->assertNotNull($order->number());
    }

    public function testSpamEmailDetection(): void
    {
        $email = EmailParser::fromFile(__DIR__ . '/../../fixtures/spam.eml');

        $this->assertTrue($email->isSpam());
        $this->assertGreaterThanOrEqual(50, $email->spamScore());
    }

    public function testLeadEmailDetection(): void
    {
        $email = EmailParser::fromFile(__DIR__ . '/../../fixtures/lead.eml');

        $this->assertTrue($email->isLead());
        $lead = $email->lead();
        $this->assertNotNull($lead->name());
    }

    public function testSupportEmailDetection(): void
    {
        $email = EmailParser::fromFile(__DIR__ . '/../../fixtures/support.eml');

        $this->assertTrue($email->isSupportRequest());
    }

    public function testPhishingEmailDetection(): void
    {
        $email = EmailParser::fromFile(__DIR__ . '/../../fixtures/phishing.eml');

        $this->assertTrue($email->isPhishing());
        $this->assertNotEquals('safe', $email->securityReport()['overall_risk']);
    }

    public function testCategoryDetectionForAllFixtures(): void
    {
        $cases = [
            'invoice' => 'invoice',
            'order'   => 'order',
            'spam'    => 'spam',
            'support' => 'support',
        ];

        foreach ($cases as $fixture => $expectedCategory) {
            $email = EmailParser::fromFile(__DIR__ . "/../../fixtures/{$fixture}.eml");
            $this->assertEquals(
                $expectedCategory,
                $email->category(),
                "Failed for fixture: {$fixture}"
            );
        }
    }

    public function testParsesMultipartEmailWithAttachment(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $this->assertTrue($email->attachments()->hasAttachments());
    }

    public function testMetadataIsPopulated(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);
        $meta = $email->metadata();

        $this->assertArrayHasKey('raw_size', $meta);
        $this->assertArrayHasKey('has_html', $meta);
        $this->assertArrayHasKey('has_plain', $meta);
        $this->assertArrayHasKey('attachment_count', $meta);
    }
}

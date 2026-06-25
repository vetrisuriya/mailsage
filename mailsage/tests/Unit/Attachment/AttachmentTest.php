<?php

declare(strict_types=1);

namespace MailSage\Tests\Unit\Attachment;

use MailSage\Attachment\Attachment;
use MailSage\Attachment\AttachmentManager;
use MailSage\EmailParser;
use MailSage\Exceptions\AttachmentException;
use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    public function testAttachmentProperties(): void
    {
        $attachment = new Attachment(
            name:     'invoice.pdf',
            mimeType: 'application/pdf',
            content:  'PDF content here',
            size:     16,
        );

        $this->assertEquals('invoice.pdf', $attachment->name());
        $this->assertEquals('pdf', $attachment->extension());
        $this->assertEquals('application/pdf', $attachment->mimeType());
        $this->assertEquals(16, $attachment->size());
        $this->assertEquals('PDF content here', $attachment->content());
    }

    public function testAttachmentRiskLevels(): void
    {
        $exe = new Attachment('malware.exe', 'application/octet-stream', '', 0);
        $zip = new Attachment('archive.zip', 'application/zip', '', 0);
        $pdf = new Attachment('doc.pdf', 'application/pdf', '', 0);
        $png = new Attachment('image.png', 'image/png', '', 0);

        $this->assertEquals('critical', $exe->riskLevel());
        $this->assertEquals('high', $zip->riskLevel());
        $this->assertEquals('low', $pdf->riskLevel());
        $this->assertEquals('safe', $png->riskLevel());
    }

    public function testAttachmentToArray(): void
    {
        $attachment = new Attachment('test.pdf', 'application/pdf', 'content', 7);
        $arr = $attachment->toArray();

        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('extension', $arr);
        $this->assertArrayHasKey('mime_type', $arr);
        $this->assertArrayHasKey('size', $arr);
        $this->assertArrayHasKey('risk_level', $arr);
    }

    public function testAttachmentSaveThrowsOnMissingDirectory(): void
    {
        $attachment = new Attachment('test.pdf', 'application/pdf', 'content', 7);

        $this->expectException(AttachmentException::class);
        $attachment->save('/nonexistent/path');
    }

    public function testAttachmentSavesToDirectory(): void
    {
        $tmpDir = sys_get_temp_dir() . '/mailsage_test_' . uniqid();
        mkdir($tmpDir);

        $attachment = new Attachment('test.txt', 'text/plain', 'Hello World', 11);
        $path = $attachment->save($tmpDir);

        $this->assertFileExists($path);
        $this->assertEquals('Hello World', file_get_contents($path));

        unlink($path);
        rmdir($tmpDir);
    }

    public function testAttachmentManagerFromInvoiceEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/invoice.eml');
        $email = EmailParser::parse($raw);

        $this->assertTrue($email->attachments()->hasAttachments());
        $this->assertEquals(1, $email->attachments()->count());
    }

    public function testAttachmentManagerHighestRisk(): void
    {
        $manager = new AttachmentManager([
            ['name' => 'doc.pdf', 'mime_type' => 'application/pdf', 'encoding' => '', 'content' => '', 'size' => 0],
            ['name' => 'virus.exe', 'mime_type' => 'application/octet-stream', 'encoding' => '', 'content' => '', 'size' => 0],
        ]);

        $this->assertEquals('critical', $manager->highestRiskLevel());
        $this->assertTrue($manager->hasDangerousAttachment());
    }
}

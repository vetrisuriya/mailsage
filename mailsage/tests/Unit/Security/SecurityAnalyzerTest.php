<?php

declare(strict_types=1);

namespace MailSage\Tests\Unit\Security;

use MailSage\EmailParser;
use MailSage\Security\SecurityAnalyzer;
use PHPUnit\Framework\TestCase;

class SecurityAnalyzerTest extends TestCase
{
    private SecurityAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new SecurityAnalyzer();
    }

    public function testDetectsPhishingEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/phishing.eml');
        $email = EmailParser::parse($raw);

        $report = $this->analyzer->analyze($email);

        $this->assertTrue($report['is_phishing']);
        $this->assertGreaterThanOrEqual(50, $report['phishing_confidence']);
    }

    public function testLegitimateEmailIsNotPhishing(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);

        $report = $this->analyzer->analyze($email);

        $this->assertFalse($report['is_phishing']);
    }

    public function testSecurityReportStructure(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/phishing.eml');
        $email = EmailParser::parse($raw);

        $report = $this->analyzer->analyze($email);

        $this->assertArrayHasKey('is_phishing', $report);
        $this->assertArrayHasKey('phishing_confidence', $report);
        $this->assertArrayHasKey('has_dangerous_attachment', $report);
        $this->assertArrayHasKey('attachment_risk', $report);
        $this->assertArrayHasKey('suspicious_urls', $report);
        $this->assertArrayHasKey('phishing_indicators', $report);
        $this->assertArrayHasKey('sender_mismatch', $report);
        $this->assertArrayHasKey('overall_risk', $report);
    }

    public function testDetectsSuspiciousUrls(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/phishing.eml');
        $email = EmailParser::parse($raw);

        $report = $this->analyzer->analyze($email);

        $this->assertNotEmpty($report['suspicious_urls']);
    }

    public function testOverallRiskLevelsAreValid(): void
    {
        $validLevels = ['safe', 'low', 'medium', 'high', 'critical'];

        $raw = file_get_contents(__DIR__ . '/../../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);
        $report = $this->analyzer->analyze($email);

        $this->assertContains($report['overall_risk'], $validLevels);
    }
}

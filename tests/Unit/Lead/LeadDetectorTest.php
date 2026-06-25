<?php

declare(strict_types=1);

namespace MailSage\Tests\Unit\Lead;

use MailSage\EmailParser;
use MailSage\Lead\Lead;
use MailSage\Lead\LeadDetector;
use PHPUnit\Framework\TestCase;

class LeadDetectorTest extends TestCase
{
    private LeadDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new LeadDetector();
    }

    public function testDetectsLeadEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/lead.eml');
        $email = EmailParser::parse($raw);

        $this->assertTrue($this->detector->detect($email));
    }

    public function testExtractsLeadName(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/lead.eml');
        $email = EmailParser::parse($raw);

        $lead = $this->detector->extract($email);
        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertNotNull($lead->name());
        $this->assertStringContainsString('Sarah', $lead->name());
    }

    public function testExtractsLeadPhone(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/lead.eml');
        $email = EmailParser::parse($raw);

        $lead = $this->detector->extract($email);
        $this->assertNotNull($lead->phone());
    }

    public function testExtractsLeadCompany(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/lead.eml');
        $email = EmailParser::parse($raw);

        $lead = $this->detector->extract($email);
        $this->assertNotNull($lead->company());
        $this->assertStringContainsString('TechStartup', $lead->company());
    }

    public function testLeadToArray(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/lead.eml');
        $email = EmailParser::parse($raw);

        $lead = $this->detector->extract($email);
        $arr = $lead->toArray();

        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('email', $arr);
        $this->assertArrayHasKey('phone', $arr);
        $this->assertArrayHasKey('company', $arr);
    }
}

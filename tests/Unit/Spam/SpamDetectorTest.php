<?php

declare(strict_types=1);

namespace MailSage\Tests\Unit\Spam;

use MailSage\EmailParser;
use MailSage\Spam\SpamDetector;
use PHPUnit\Framework\TestCase;

class SpamDetectorTest extends TestCase
{
    private SpamDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new SpamDetector();
    }

    public function testDetectsSpamEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/spam.eml');
        $email = EmailParser::parse($raw);

        $this->assertTrue($this->detector->detect($email));
        $this->assertGreaterThanOrEqual(50, $this->detector->score($email));
    }

    public function testDoesNotFlagLegitimateEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/sample.eml');
        $email = EmailParser::parse($raw);

        $score = $this->detector->score($email);
        $this->assertLessThan(50, $score);
    }

    public function testScoreIsWithinRange(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/spam.eml');
        $email = EmailParser::parse($raw);

        $score = $this->detector->score($email);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function testAnalyzeReturnsStructuredResult(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/spam.eml');
        $email = EmailParser::parse($raw);

        $result = $this->detector->analyze($email);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('is_spam', $result);
        $this->assertArrayHasKey('indicators', $result);
        $this->assertIsArray($result['indicators']);
    }

    public function testConfidenceMatchesScore(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/spam.eml');
        $email = EmailParser::parse($raw);

        $this->assertEquals($this->detector->score($email), $this->detector->confidence($email));
    }
}

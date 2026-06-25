<?php

declare(strict_types=1);

namespace MailSage\Tests\Unit\Parser;

use MailSage\Parser\HeaderParser;
use PHPUnit\Framework\TestCase;

class HeaderParserTest extends TestCase
{
    private HeaderParser $parser;

    protected function setUp(): void
    {
        $this->parser = new HeaderParser();
    }

    public function testParsesBasicHeaders(): void
    {
        $raw = "From: john@example.com\nTo: jane@example.com\nSubject: Hello World";
        $headers = $this->parser->parse($raw);

        $this->assertArrayHasKey('from', $headers);
        $this->assertArrayHasKey('to', $headers);
        $this->assertArrayHasKey('subject', $headers);
        $this->assertEquals('john@example.com', $headers['from']);
        $this->assertEquals('Hello World', $headers['subject']);
    }

    public function testUnfoldsFoldedHeaders(): void
    {
        $raw = "Subject: This is a very\n long subject\n  that spans multiple lines";
        $headers = $this->parser->parse($raw);

        $this->assertStringNotContainsString("\n", (string) $headers['subject']);
    }

    public function testNormalizesHeaderNamesToLowercase(): void
    {
        $raw = "From: a@b.com\nCONTENT-TYPE: text/plain";
        $headers = $this->parser->parse($raw);

        $this->assertArrayHasKey('from', $headers);
        $this->assertArrayHasKey('content-type', $headers);
    }

    public function testCollectsMultipleReceivedHeaders(): void
    {
        $raw = "Received: from a.com\nReceived: from b.com\nSubject: test";
        $headers = $this->parser->parse($raw);

        $this->assertIsArray($headers['received']);
        $this->assertCount(2, $headers['received']);
    }

    public function testSplitsHeadersAndBody(): void
    {
        $raw = "From: a@b.com\nSubject: test\n\nThis is the body.";
        $result = $this->parser->splitHeadersAndBody($raw);

        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('body', $result);
        $this->assertStringContainsString('From:', $result['headers']);
        $this->assertEquals('This is the body.', $result['body']);
    }

    public function testExtractsSpfResult(): void
    {
        $authResults = 'mx.example.com; spf=pass smtp.mailfrom=example.com';
        $this->assertEquals('pass', $this->parser->extractSpfResult($authResults));
    }

    public function testExtractsDkimResult(): void
    {
        $authResults = 'mx.example.com; dkim=fail header.d=example.com';
        $this->assertEquals('fail', $this->parser->extractDkimResult($authResults));
    }

    public function testExtractsDmarcResult(): void
    {
        $authResults = 'mx.example.com; dmarc=pass';
        $this->assertEquals('pass', $this->parser->extractDmarcResult($authResults));
    }

    public function testReturnsNullForMissingAuthResults(): void
    {
        $this->assertNull($this->parser->extractSpfResult('no auth here'));
        $this->assertNull($this->parser->extractDkimResult('no auth here'));
        $this->assertNull($this->parser->extractDmarcResult('no auth here'));
    }

    public function testHandlesEmptyInput(): void
    {
        $headers = $this->parser->parse('');
        $this->assertIsArray($headers);
        $this->assertEmpty($headers);
    }

    public function testHandlesHeadersWithoutColon(): void
    {
        $raw = "From: a@b.com\nMalformedLine\nSubject: test";
        $headers = $this->parser->parse($raw);

        $this->assertArrayHasKey('from', $headers);
        $this->assertArrayHasKey('subject', $headers);
    }
}

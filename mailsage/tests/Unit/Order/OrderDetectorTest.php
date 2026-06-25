<?php

declare(strict_types=1);

namespace MailSage\Tests\Unit\Order;

use MailSage\EmailParser;
use MailSage\Order\Order;
use MailSage\Order\OrderDetector;
use PHPUnit\Framework\TestCase;

class OrderDetectorTest extends TestCase
{
    private OrderDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new OrderDetector();
    }

    public function testDetectsOrderEmail(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/order.eml');
        $email = EmailParser::parse($raw);

        $this->assertTrue($this->detector->detect($email));
    }

    public function testExtractsOrderNumber(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/order.eml');
        $email = EmailParser::parse($raw);

        $order = $this->detector->extract($email);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->number());
    }

    public function testExtractsOrderAmount(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/order.eml');
        $email = EmailParser::parse($raw);

        $order = $this->detector->extract($email);
        $this->assertNotNull($order->amount());
        $this->assertEquals(89.95, $order->amount());
    }

    public function testDetectsShopifyPlatform(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/order.eml');
        $email = EmailParser::parse($raw);

        $order = $this->detector->extract($email);
        $this->assertEquals('shopify', $order->platform());
    }

    public function testOrderToArray(): void
    {
        $raw = file_get_contents(__DIR__ . '/../../../fixtures/order.eml');
        $email = EmailParser::parse($raw);

        $order = $this->detector->extract($email);
        $arr = $order->toArray();

        $this->assertArrayHasKey('number', $arr);
        $this->assertArrayHasKey('customer', $arr);
        $this->assertArrayHasKey('amount', $arr);
        $this->assertArrayHasKey('currency', $arr);
        $this->assertArrayHasKey('platform', $arr);
    }
}

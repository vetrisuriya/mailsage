<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MailSage\EmailParser;

// Example raw email
$rawEmail = <<<EMAIL
From: John Smith <john@example.com>
To: Jane Doe <jane@company.com>
Subject: Hello from MailSage
Date: Thu, 25 Jun 2026 10:00:00 +0000
Message-ID: <example.001@example.com>
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8

Hi Jane,

This is a test email parsed by MailSage.

Best regards,
John
EMAIL;

// Parse the email
$email = EmailParser::parse($rawEmail);

echo "=== MailSage - Parse Email Example ===\n\n";
echo "Subject  : " . $email->subject() . "\n";
echo "From     : " . $email->sender()['name'] . " <" . $email->sender()['email'] . ">\n";
echo "To       : " . $email->recipient()[0]['email'] . "\n";
echo "Date     : " . $email->date() . "\n";
echo "Category : " . $email->category() . "\n";
echo "Is Spam  : " . ($email->isSpam() ? 'Yes' : 'No') . "\n";
echo "\nBody:\n" . $email->body() . "\n";
echo "\nFull JSON:\n" . $email->toJson() . "\n";

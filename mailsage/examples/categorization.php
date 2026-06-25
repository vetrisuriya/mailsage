<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MailSage\Categorization\Category;
use MailSage\Categorization\CategoryDetector;
use MailSage\EmailParser;

echo "=== MailSage - Email Categorization Example ===\n\n";

// Register a custom category
Category::register('legal', ['contract', 'nda', 'agreement', 'legal notice', 'terms']);

$detector = new CategoryDetector();

$fixtures = [
    'sample.eml'   => 'General Email',
    'invoice.eml'  => 'Invoice Email',
    'order.eml'    => 'Order Email',
    'spam.eml'     => 'Spam Email',
    'lead.eml'     => 'Lead Email',
    'support.eml'  => 'Support Email',
    'phishing.eml' => 'Phishing Email',
];

foreach ($fixtures as $file => $label) {
    $email      = EmailParser::fromFile(__DIR__ . "/../fixtures/{$file}");
    $category   = $detector->detect($email);
    $confidence = $detector->confidence($email);

    printf("%-20s => Category: %-15s  Confidence: %d%%\n",
        $label, $category, $confidence);
}

// Use the Email model directly
echo "\n--- Via Email Model ---\n";
$email = EmailParser::fromFile(__DIR__ . '/../fixtures/invoice.eml');
echo "Category   : " . $email->category() . "\n";
echo "Confidence : " . $email->confidence() . "%\n";
echo "Is Invoice : " . ($email->isInvoice() ? 'Yes' : 'No') . "\n";
echo "Is Spam    : " . ($email->isSpam() ? 'Yes' : 'No') . "\n";
echo "Is Order   : " . ($email->isOrder() ? 'Yes' : 'No') . "\n";

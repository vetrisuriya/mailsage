<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MailSage\EmailParser;

echo "=== MailSage - Invoice Detection Example ===\n\n";

$email = EmailParser::fromFile(__DIR__ . '/../fixtures/invoice.eml');

echo "Is Invoice : " . ($email->isInvoice() ? 'Yes' : 'No') . "\n";

if ($email->isInvoice()) {
    $invoice = $email->invoice();

    echo "\n--- Invoice Details ---\n";
    echo "Number   : " . ($invoice->number() ?? 'N/A') . "\n";
    echo "Date     : " . ($invoice->date() ?? 'N/A') . "\n";
    echo "Amount   : " . ($invoice->amount() !== null ? number_format($invoice->amount(), 2) : 'N/A') . "\n";
    echo "Currency : " . ($invoice->currency() ?? 'N/A') . "\n";
    echo "Vendor   : " . ($invoice->vendor() ?? 'N/A') . "\n";

    echo "\nAs Array:\n";
    print_r($invoice->toArray());
}

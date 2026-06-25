<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MailSage\EmailParser;

echo "=== MailSage - Lead Detection Example ===\n\n";

$email = EmailParser::fromFile(__DIR__ . '/../fixtures/lead.eml');

echo "Is Lead     : " . ($email->isLead() ? 'Yes' : 'No') . "\n";
echo "Category    : " . $email->category() . "\n";
echo "Confidence  : " . $email->confidence() . "%\n\n";

if ($email->isLead()) {
    $lead = $email->lead();

    echo "--- Lead Details ---\n";
    echo "Name    : " . ($lead->name() ?? 'N/A') . "\n";
    echo "Email   : " . ($lead->email() ?? 'N/A') . "\n";
    echo "Phone   : " . ($lead->phone() ?? 'N/A') . "\n";
    echo "Company : " . ($lead->company() ?? 'N/A') . "\n";
}

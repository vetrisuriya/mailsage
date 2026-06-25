<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MailSage\EmailParser;
use MailSage\Spam\SpamDetector;

echo "=== MailSage - Spam Detection Example ===\n\n";

$files = [
    'sample.eml'  => 'Legitimate Email',
    'spam.eml'    => 'Spam Email',
    'invoice.eml' => 'Invoice Email',
];

$detector = new SpamDetector();

foreach ($files as $file => $label) {
    $email = EmailParser::fromFile(__DIR__ . "/../fixtures/{$file}");
    $analysis = $detector->analyze($email);

    echo "--- {$label} ---\n";
    echo "Score   : " . $analysis['score'] . "/100\n";
    echo "Is Spam : " . ($analysis['is_spam'] ? 'YES' : 'No') . "\n";
    if ($analysis['indicators']) {
        echo "Flags   :\n";
        foreach ($analysis['indicators'] as $flag) {
            echo "  - {$flag}\n";
        }
    }
    echo "\n";
}

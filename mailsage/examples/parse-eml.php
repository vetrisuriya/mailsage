<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MailSage\EmailParser;
use MailSage\Exceptions\InvalidEMLException;

echo "=== MailSage - Parse EML File Example ===\n\n";

$emlFile = __DIR__ . '/../fixtures/sample.eml';

try {
    $email = EmailParser::fromFile($emlFile);

    echo "Successfully parsed: {$emlFile}\n\n";
    echo "Subject  : " . $email->subject() . "\n";
    echo "From     : " . $email->sender()['email'] . "\n";
    echo "To       : " . $email->recipient()[0]['email'] . "\n";
    echo "Category : " . $email->category() . "\n";
    echo "Confidence: " . $email->confidence() . "%\n";

    $header = $email->headerReport();
    echo "\n--- Header Auth Report ---\n";
    echo "SPF  : " . ($header['spf'] ?? 'none') . "\n";
    echo "DKIM : " . ($header['dkim'] ?? 'none') . "\n";
    echo "DMARC: " . ($header['dmarc'] ?? 'none') . "\n";

} catch (InvalidEMLException $e) {
    echo "EML Error: " . $e->getMessage() . "\n";
}

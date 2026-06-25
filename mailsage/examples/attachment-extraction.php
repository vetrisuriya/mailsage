<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MailSage\EmailParser;

echo "=== MailSage - Attachment Extraction Example ===\n\n";

$email = EmailParser::fromFile(__DIR__ . '/../fixtures/invoice.eml');

$attachments = $email->attachments();

echo "Attachment count : " . $attachments->count() . "\n";
echo "Has attachments  : " . ($attachments->hasAttachments() ? 'Yes' : 'No') . "\n";
echo "Highest risk     : " . $attachments->highestRiskLevel() . "\n\n";

foreach ($attachments->all() as $i => $attachment) {
    $n = $i + 1;
    echo "--- Attachment #{$n} ---\n";
    echo "Name      : " . $attachment->name() . "\n";
    echo "Extension : " . $attachment->extension() . "\n";
    echo "MIME Type : " . $attachment->mimeType() . "\n";
    echo "Size      : " . $attachment->size() . " bytes\n";
    echo "Risk      : " . $attachment->riskLevel() . "\n\n";
}

// Save attachments
$saveDir = sys_get_temp_dir() . '/mailsage_attachments';
if (!is_dir($saveDir)) {
    mkdir($saveDir, 0755, true);
}

if ($attachments->hasAttachments()) {
    $saved = $email->saveAttachments($saveDir);
    echo "Saved to:\n";
    foreach ($saved as $path) {
        echo "  - {$path}\n";
    }
}

<?php

declare(strict_types=1);

namespace MailSage\Attachment;

use MailSage\Contracts\AttachmentInterface;
use MailSage\Exceptions\AttachmentException;
use MailSage\Helpers\StringHelper;

class Attachment implements AttachmentInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $mimeType,
        private readonly string $content,
        private readonly int    $size,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function extension(): string
    {
        return StringHelper::getExtension($this->name);
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function content(): string
    {
        return $this->content;
    }

    /**
     * Save the attachment to the given directory.
     * Returns the full path to the saved file.
     *
     * @throws AttachmentException
     */
    public function save(string $directory): string
    {
        if (!is_dir($directory)) {
            throw AttachmentException::directoryNotFound($directory);
        }

        if (!is_writable($directory)) {
            throw AttachmentException::directoryNotWritable($directory);
        }

        $filename = StringHelper::sanitizeFilename($this->name);
        $path     = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        // Avoid overwriting by appending a counter
        if (file_exists($path)) {
            $info     = pathinfo($filename);
            $base     = $info['filename'] ?? $filename;
            $ext      = isset($info['extension']) ? '.' . $info['extension'] : '';
            $counter  = 1;
            do {
                $path = rtrim($directory, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . "{$base}_{$counter}{$ext}";
                $counter++;
            } while (file_exists($path));
        }

        $written = file_put_contents($path, $this->content);

        if ($written === false) {
            throw AttachmentException::saveFailed($this->name);
        }

        return $path;
    }

    /**
     * Get the risk level of this attachment based on its extension and MIME type.
     * Levels: safe | low | medium | high | critical
     */
    public function riskLevel(): string
    {
        $ext = $this->extension();

        $critical = ['exe', 'bat', 'cmd', 'scr', 'pif', 'com', 'vbs', 'vbe', 'js', 'jse', 'ps1', 'psm1', 'msi', 'reg'];
        $high     = ['zip', 'rar', '7z', 'tar', 'gz', 'iso', 'dmg', 'dll', 'sys', 'drv'];
        $medium   = ['docm', 'xlsm', 'pptm', 'xlsb', 'dotm'];
        $low      = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

        if (in_array($ext, $critical, true)) {
            return 'critical';
        }
        if (in_array($ext, $high, true)) {
            return 'high';
        }
        if (in_array($ext, $medium, true)) {
            return 'medium';
        }
        if (in_array($ext, $low, true)) {
            return 'low';
        }

        return 'safe';
    }

    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'extension'  => $this->extension(),
            'mime_type'  => $this->mimeType,
            'size'       => $this->size,
            'risk_level' => $this->riskLevel(),
        ];
    }
}

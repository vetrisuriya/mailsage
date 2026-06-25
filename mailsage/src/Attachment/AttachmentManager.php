<?php

declare(strict_types=1);

namespace MailSage\Attachment;

use MailSage\Exceptions\AttachmentException;

class AttachmentManager
{
    /** @var Attachment[] */
    private array $attachments = [];

    /**
     * @param array<int, array{name: string, mime_type: string, encoding: string, content: string, size: int}> $rawAttachments
     */
    public function __construct(array $rawAttachments = [])
    {
        foreach ($rawAttachments as $raw) {
            $this->attachments[] = new Attachment(
                name:     $raw['name'] ?? 'unnamed',
                mimeType: $raw['mime_type'] ?? 'application/octet-stream',
                content:  $raw['content'] ?? '',
                size:     $raw['size'] ?? 0,
            );
        }
    }

    /**
     * @return Attachment[]
     */
    public function all(): array
    {
        return $this->attachments;
    }

    public function count(): int
    {
        return count($this->attachments);
    }

    public function hasAttachments(): bool
    {
        return $this->attachments !== [];
    }

    /**
     * Filter attachments by extension.
     *
     * @return Attachment[]
     */
    public function filterByExtension(string ...$extensions): array
    {
        return array_values(array_filter(
            $this->attachments,
            fn (Attachment $a) => in_array($a->extension(), $extensions, true)
        ));
    }

    /**
     * Save all attachments to a directory.
     * Returns array of saved file paths.
     *
     * @return string[]
     * @throws AttachmentException
     */
    public function saveAll(string $directory): array
    {
        $paths = [];
        foreach ($this->attachments as $attachment) {
            $paths[] = $attachment->save($directory);
        }

        return $paths;
    }

    /**
     * Get highest risk level across all attachments.
     */
    public function highestRiskLevel(): string
    {
        $order = ['safe', 'low', 'medium', 'high', 'critical'];
        $highest = 'safe';

        foreach ($this->attachments as $attachment) {
            $level = $attachment->riskLevel();
            if (array_search($level, $order) > array_search($highest, $order)) {
                $highest = $level;
            }
        }

        return $highest;
    }

    /**
     * Check whether any attachment is considered dangerous.
     */
    public function hasDangerousAttachment(): bool
    {
        foreach ($this->attachments as $attachment) {
            if (in_array($attachment->riskLevel(), ['high', 'critical'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{name: string, extension: string, mime_type: string, size: int, risk_level: string}>
     */
    public function toArray(): array
    {
        return array_map(fn (Attachment $a) => $a->toArray(), $this->attachments);
    }
}

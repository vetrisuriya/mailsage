<?php

declare(strict_types=1);

namespace MailSage\Contracts;

interface AttachmentInterface
{
    public function name(): string;
    public function extension(): string;
    public function mimeType(): string;
    public function size(): int;
    public function content(): string;
    public function save(string $directory): string;
    public function toArray(): array;
}

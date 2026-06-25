<?php

declare(strict_types=1);

namespace MailSage\Contracts;

interface ExporterInterface
{
    public function toArray(): array;
    public function toJson(int $flags = JSON_PRETTY_PRINT): string;
    public function toCsv(): string;
}

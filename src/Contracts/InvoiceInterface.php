<?php

declare(strict_types=1);

namespace MailSage\Contracts;

interface InvoiceInterface
{
    public function number(): ?string;
    public function date(): ?string;
    public function amount(): ?float;
    public function currency(): ?string;
    public function vendor(): ?string;
    public function toArray(): array;
}

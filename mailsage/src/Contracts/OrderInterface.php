<?php

declare(strict_types=1);

namespace MailSage\Contracts;

interface OrderInterface
{
    public function number(): ?string;
    public function customer(): ?string;
    public function amount(): ?float;
    public function currency(): ?string;
    public function platform(): ?string;
    public function toArray(): array;
}

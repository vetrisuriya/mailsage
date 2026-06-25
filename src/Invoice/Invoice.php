<?php

declare(strict_types=1);

namespace MailSage\Invoice;

use MailSage\Contracts\InvoiceInterface;

class Invoice implements InvoiceInterface
{
    public function __construct(
        private readonly ?string $number,
        private readonly ?string $date,
        private readonly ?float  $amount,
        private readonly ?string $currency,
        private readonly ?string $vendor,
    ) {}

    public function number(): ?string
    {
        return $this->number;
    }

    public function date(): ?string
    {
        return $this->date;
    }

    public function amount(): ?float
    {
        return $this->amount;
    }

    public function currency(): ?string
    {
        return $this->currency;
    }

    public function vendor(): ?string
    {
        return $this->vendor;
    }

    public function toArray(): array
    {
        return [
            'number'   => $this->number,
            'date'     => $this->date,
            'amount'   => $this->amount,
            'currency' => $this->currency,
            'vendor'   => $this->vendor,
        ];
    }
}

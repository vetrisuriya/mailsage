<?php

declare(strict_types=1);

namespace MailSage\Order;

use MailSage\Contracts\OrderInterface;

class Order implements OrderInterface
{
    public function __construct(
        private readonly ?string $number,
        private readonly ?string $customer,
        private readonly ?float  $amount,
        private readonly ?string $currency,
        private readonly ?string $platform,
    ) {}

    public function number(): ?string
    {
        return $this->number;
    }

    public function customer(): ?string
    {
        return $this->customer;
    }

    public function amount(): ?float
    {
        return $this->amount;
    }

    public function currency(): ?string
    {
        return $this->currency;
    }

    public function platform(): ?string
    {
        return $this->platform;
    }

    public function toArray(): array
    {
        return [
            'number'   => $this->number,
            'customer' => $this->customer,
            'amount'   => $this->amount,
            'currency' => $this->currency,
            'platform' => $this->platform,
        ];
    }
}

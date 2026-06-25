<?php

declare(strict_types=1);

namespace MailSage\Lead;

use MailSage\Contracts\LeadInterface;

class Lead implements LeadInterface
{
    public function __construct(
        private readonly ?string $name,
        private readonly ?string $email,
        private readonly ?string $phone,
        private readonly ?string $company,
    ) {}

    public function name(): ?string
    {
        return $this->name;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function company(): ?string
    {
        return $this->company;
    }

    public function toArray(): array
    {
        return [
            'name'    => $this->name,
            'email'   => $this->email,
            'phone'   => $this->phone,
            'company' => $this->company,
        ];
    }
}

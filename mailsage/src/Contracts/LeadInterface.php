<?php

declare(strict_types=1);

namespace MailSage\Contracts;

interface LeadInterface
{
    public function name(): ?string;
    public function email(): ?string;
    public function phone(): ?string;
    public function company(): ?string;
    public function toArray(): array;
}

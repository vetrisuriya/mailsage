<?php

declare(strict_types=1);

namespace MailSage\Contracts;

use MailSage\Models\Email;

interface DetectorInterface
{
    /**
     * Detect whether the email matches this detector's criteria.
     */
    public function detect(Email $email): bool;

    /**
     * Return a confidence score between 0 and 100.
     */
    public function confidence(Email $email): int;
}

<?php

declare(strict_types=1);

namespace MailSage\Parser;

class AddressParser
{
    /**
     * Parse an email address header value into name and address parts.
     *
     * Supports formats:
     *   "Display Name <email@example.com>"
     *   "email@example.com"
     *   "Display Name" <email@example.com>
     *
     * @return array{name: string, email: string, raw: string}
     */
    public function parseSingle(string $value): array
    {
        $value = trim($value);
        $decoded = mb_decode_mimeheader($value) ?: $value;

        // Format: "Display Name <email@example.com>"
        if (preg_match('/^(.+?)\s*<([^>]+)>\s*$/', $decoded, $m)) {
            return [
                'name'  => trim($m[1], '"\''),
                'email' => strtolower(trim($m[2])),
                'raw'   => $value,
            ];
        }

        // Format: bare email
        if (filter_var($decoded, FILTER_VALIDATE_EMAIL)) {
            return [
                'name'  => '',
                'email' => strtolower($decoded),
                'raw'   => $value,
            ];
        }

        // Try extracting email from messy string
        if (preg_match('/([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/', $decoded, $m)) {
            $email = strtolower($m[1]);
            $name  = trim(str_replace($m[0], '', $decoded), ' <>"\'');

            return [
                'name'  => $name,
                'email' => $email,
                'raw'   => $value,
            ];
        }

        return ['name' => $decoded, 'email' => '', 'raw' => $value];
    }

    /**
     * Parse a comma-separated list of addresses.
     *
     * @return array<int, array{name: string, email: string, raw: string}>
     */
    public function parseList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        // Split on commas that are outside angle brackets
        $addresses = $this->splitAddressList($value);
        $results   = [];

        foreach ($addresses as $address) {
            $address = trim($address);
            if ($address !== '') {
                $results[] = $this->parseSingle($address);
            }
        }

        return $results;
    }

    /**
     * Split a header value into individual address tokens, respecting angle brackets.
     *
     * @return string[]
     */
    private function splitAddressList(string $value): array
    {
        $addresses = [];
        $current   = '';
        $depth     = 0;

        for ($i = 0; $i < strlen($value); $i++) {
            $char = $value[$i];
            if ($char === '<') {
                $depth++;
                $current .= $char;
            } elseif ($char === '>') {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $addresses[] = $current;
                $current     = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $addresses[] = $current;
        }

        return $addresses;
    }

    /**
     * Format an address array back to a display string.
     *
     * @param array{name: string, email: string, raw: string} $address
     */
    public function format(array $address): string
    {
        if ($address['name'] !== '' && $address['email'] !== '') {
            return "{$address['name']} <{$address['email']}>";
        }

        return $address['email'] ?: $address['name'] ?: $address['raw'];
    }
}

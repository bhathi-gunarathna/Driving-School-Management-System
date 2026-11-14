<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function toCents(string|int $amount): int
    {
        $amount = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Money values must be non-negative and have no more than two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public static function fromCents(int $cents): string
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money values cannot be negative.');
        }

        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** @return list<int> */
    public static function splitCents(int $totalCents, int $parts): array
    {
        if ($totalCents < 0 || $parts < 1) {
            throw new InvalidArgumentException('A non-negative amount and at least one part are required.');
        }

        $base = intdiv($totalCents, $parts);
        $remainder = $totalCents % $parts;

        return array_map(
            fn (int $index): int => $base + ($index < $remainder ? 1 : 0),
            range(0, $parts - 1),
        );
    }
}

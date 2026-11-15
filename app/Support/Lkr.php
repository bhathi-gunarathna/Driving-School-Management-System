<?php

namespace App\Support;

class Lkr
{
    public static function format(string|int|float|null $amount): string
    {
        return 'LKR '.number_format((float) ($amount ?? 0), 2, '.', ',');
    }
}

<?php

namespace App\Support;

class Money
{
    public static function rupiah(int|float $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }

    /**
     * Compact form used on price cards, e.g. Rp49rb.
     */
    public static function rupiahShort(int $amount): string
    {
        if ($amount >= 1000 && $amount % 1000 === 0) {
            return 'Rp'.number_format($amount / 1000, 0, ',', '.').'rb';
        }

        return self::rupiah($amount);
    }
}

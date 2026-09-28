<?php

namespace App\Support;

/**
 * Formats menu and order figures.
 *
 * The house prints whole pesos without noise, and only falls back to cents
 * when an amount genuinely carries a fraction.
 */
class Money
{
    /**
     * A currency-prefixed amount, e.g. ₱120 or ₱120.50.
     */
    public static function format(float|int|string|null $amount = null): string
    {
        return '₱'.static::plain($amount);
    }

    /**
     * The same amount without its currency mark.
     */
    public static function plain(float|int|string|null $amount = null): string
    {
        $value = (float) ($amount ?? 0);

        if (abs($value - round($value)) < 0.005) {
            return number_format($value, 0);
        }

        return number_format($value, 2);
    }
}

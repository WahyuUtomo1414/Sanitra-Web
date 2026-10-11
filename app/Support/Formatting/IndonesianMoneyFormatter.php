<?php

namespace App\Support\Formatting;

class IndonesianMoneyFormatter
{
    public static function formatNumber(int|float|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', '.');
    }

    public static function formatRupiah(int|float|string|null $amount): string
    {
        return 'Rp '.self::formatNumber($amount);
    }

    public static function formatInput(int|float|string|null $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return number_format((float) $amount, 0, '.', '');
    }

    public static function parseInput(int|float|string|null $amount): string
    {
        return preg_replace('/\D/', '', (string) $amount) ?: '0';
    }
}

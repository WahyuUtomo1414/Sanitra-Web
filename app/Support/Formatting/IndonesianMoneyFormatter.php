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

        $value = (string) $amount;

        if (preg_match('/\.\d{6}$/', $value)) {
            $value = substr($value, 0, strrpos($value, '.'));
        }

        return preg_replace('/\D/', '', $value) ?: '0';
    }

    public static function parseInput(int|float|string|null $amount): string
    {
        $value = (string) $amount;

        if (preg_match('/\.\d{6}$/', $value)) {
            $value = substr($value, 0, strrpos($value, '.'));
        }

        return preg_replace('/\D/', '', $value) ?: '0';
    }
}

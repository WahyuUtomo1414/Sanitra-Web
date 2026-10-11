<?php

namespace App\Filament\Forms\Components;

use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class MoneyInput
{
    /**
     * Strip suffix `.123456` (persis 6 digit) sebelum dilempar ke `$money`.
     * Mencegah value DB `1800000.000000` (cast `decimal:6`) diperlakukan sebagai
     * `1.800.000.000.000` oleh mask ribuan. Thousand grouping (`.000` = 3 digit)
     * tetap aman karena regex hanya match 6 digit.
     */
    private const MASK = <<<'JS'
$money($input.replace(/\.\d{6}$/, ''), ',', '.', 0)
JS;

    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('Rp')
            ->mask(RawJs::make(self::MASK))
            ->stripCharacters('.')
            ->numeric()
            ->formatStateUsing(fn (int|float|string|null $state): ?string => IndonesianMoneyFormatter::formatInput($state))
            ->afterStateHydrated(function (TextInput $component, int|float|string|null $state): void {
                $component->state(IndonesianMoneyFormatter::formatInput($state));
            });
    }

    public static function display(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('Rp')
            ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatNumber($state))
            ->afterStateHydrated(function (TextInput $component, int|float|string|null $state): void {
                $component->state(IndonesianMoneyFormatter::formatNumber($state));
            });
    }
}

<?php

namespace App\Filament\Forms\Components;

use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class MoneyInput
{
    private const MASK = <<<'JS'
$money($input.replace(/\.\d{6}$/, ''), ',', '.', 0)
JS;

    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('Rp')
            ->mask(RawJs::make(self::MASK))
            ->afterStateHydrated(function (TextInput $component, int|float|string|null $state): void {
                $digits = IndonesianMoneyFormatter::parseInput($state);
                $component->state($digits !== '0' ? IndonesianMoneyFormatter::formatNumber($digits) : null);
            })
            ->dehydrateStateUsing(fn (int|float|string|null $state): ?string => IndonesianMoneyFormatter::parseInput($state) ?: null);
    }

    public static function display(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('Rp')
            ->afterStateHydrated(function (TextInput $component, int|float|string|null $state): void {
                $component->state(IndonesianMoneyFormatter::formatNumber(
                    IndonesianMoneyFormatter::parseInput($state)
                ));
            });
    }
}

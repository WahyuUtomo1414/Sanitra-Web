<?php

namespace App\Filament\Forms\Components;

use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

class MoneyInput
{
    private const MASK = '$money($input, \',\', \'.\', 0)';

    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->mask(RawJs::make(self::MASK))
            ->stripCharacters('.')
            ->numeric()
            ->prefix('Rp')
            ->formatStateUsing(fn (int|float|string|null $state): ?string => IndonesianMoneyFormatter::formatInput($state));
    }

    public static function display(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('Rp')
            ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatNumber($state));
    }
}

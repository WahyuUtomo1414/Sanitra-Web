<?php

namespace App\Enums;

enum UnitType: string
{
    case Waktu = 'waktu';
    case Barang = 'barang';

    public function label(): string
    {
        return match ($this) {
            self::Waktu => 'Waktu',
            self::Barang => 'Barang',
        };
    }

    public static function options(): array
    {
        return [
            self::Waktu->value => self::Waktu->label(),
            self::Barang->value => self::Barang->label(),
        ];
    }
}

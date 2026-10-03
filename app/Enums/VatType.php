<?php

namespace App\Enums;

enum VatType: string
{
    case None = 'none';
    case Include = 'include';
    case Exclude = 'exclude';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanpa PPN',
            self::Include => 'PPN Include',
            self::Exclude => 'PPN Exclude',
        };
    }
}

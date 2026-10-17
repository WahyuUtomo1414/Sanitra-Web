<?php

namespace App\Enums\Status;

enum OrderCloseReason: string
{
    case Completed = 'completed';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Selesai',
            self::Manual => 'Ditutup',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Manual => 'gray',
        };
    }
}

<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum ProjectStatus: string
{
    case OnProgress = 'on_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public static function type(): StatusType
    {
        return StatusType::Project;
    }

    public function label(): string
    {
        return match ($this) {
            self::OnProgress => 'Berjalan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OnProgress => 'info',
            self::Completed => 'success',
            self::Cancelled => 'gray',
        };
    }
}

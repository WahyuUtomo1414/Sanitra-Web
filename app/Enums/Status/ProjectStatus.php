<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum ProjectStatus: string
{
    case Ongoing = 'ongoing';
    case OnHold = 'on_hold';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public static function type(): StatusType
    {
        return StatusType::Project;
    }

    public function label(): string
    {
        return match ($this) {
            self::Ongoing => 'Berjalan',
            self::OnHold => 'Ditunda',
            self::Closed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ongoing => 'info',
            self::OnHold => 'warning',
            self::Closed => 'success',
            self::Cancelled => 'gray',
        };
    }
}

<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum ProjectDtStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public static function type(): StatusType
    {
        return StatusType::ProjectDt;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum Mulai',
            self::InProgress => 'Sedang Berjalan',
            self::Completed => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'info',
            self::Completed => 'success',
        };
    }
}

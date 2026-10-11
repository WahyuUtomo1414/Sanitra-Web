<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum ProjectStageStatus: string
{
    case Draft = 'draft';
    case OnProgress = 'on_progress';
    case Completed = 'completed';
    case Blocked = 'blocked';

    public static function type(): StatusType
    {
        return StatusType::ProjectDt;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Belum Dimulai',
            self::OnProgress => 'Sedang Dikerjakan',
            self::Completed => 'Selesai',
            self::Blocked => 'Terkendala',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::OnProgress => 'info',
            self::Completed => 'success',
            self::Blocked => 'warning',
        };
    }
}

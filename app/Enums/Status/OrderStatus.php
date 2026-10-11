<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum OrderStatus: string
{
    case WaitingDp = 'waiting_dp';
    case OnProgress = 'on_progress';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public static function type(): StatusType
    {
        return StatusType::Order;
    }

    public function label(): string
    {
        return match ($this) {
            self::WaitingDp => 'Menunggu DP',
            self::OnProgress => 'Berjalan',
            self::Closed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WaitingDp => 'warning',
            self::OnProgress => 'info',
            self::Closed => 'success',
            self::Cancelled => 'gray',
        };
    }
}

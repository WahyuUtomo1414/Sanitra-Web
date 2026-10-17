<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';

    public static function type(): StatusType
    {
        return StatusType::Order;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Menunggu DP',
            self::Open => 'Berjalan',
            self::Closed => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Open => 'info',
            self::Closed => 'success',
        };
    }
}

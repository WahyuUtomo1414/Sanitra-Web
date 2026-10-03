<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public static function type(): StatusType
    {
        return StatusType::Payment;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Confirmed => 'Terkonfirmasi',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::Rejected => 'danger',
        };
    }
}

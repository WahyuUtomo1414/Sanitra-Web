<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum OrderPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';

    public static function type(): StatusType
    {
        return StatusType::OrderPayment;
    }

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::Partial => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'gray',
            self::Partial => 'warning',
            self::Paid => 'success',
        };
    }
}

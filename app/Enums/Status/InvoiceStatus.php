<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Void = 'void';

    public static function type(): StatusType
    {
        return StatusType::Invoice;
    }

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::Partial => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
            self::Void => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'gray',
            self::Partial => 'warning',
            self::Paid => 'success',
            self::Void => 'gray',
        };
    }
}

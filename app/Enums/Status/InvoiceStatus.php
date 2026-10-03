<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Void = 'void';
    case Overdue = 'overdue';

    public static function type(): StatusType
    {
        return StatusType::Invoice;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Diterbitkan',
            self::PartiallyPaid => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
            self::Void => 'Dibatalkan',
            self::Overdue => 'Jatuh Tempo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Issued => 'info',
            self::PartiallyPaid => 'warning',
            self::Paid => 'success',
            self::Void => 'gray',
            self::Overdue => 'danger',
        };
    }
}

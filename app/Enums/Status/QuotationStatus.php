<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public static function type(): StatusType
    {
        return StatusType::Quotation;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Dikirim',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}

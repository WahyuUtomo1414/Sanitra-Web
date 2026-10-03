<?php

namespace App\Enums\Status;

use App\Enums\StatusType;

enum TermStatus: string
{
    case NotReady = 'not_ready';
    case ReadyToBill = 'ready_to_bill';
    case Invoiced = 'invoiced';
    case Paid = 'paid';

    public static function type(): StatusType
    {
        return StatusType::Term;
    }

    public function label(): string
    {
        return match ($this) {
            self::NotReady => 'Belum Siap',
            self::ReadyToBill => 'Siap Ditagih',
            self::Invoiced => 'Sudah Ditagih',
            self::Paid => 'Lunas',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotReady => 'gray',
            self::ReadyToBill => 'warning',
            self::Invoiced => 'info',
            self::Paid => 'success',
        };
    }
}

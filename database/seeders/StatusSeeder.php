<?php

namespace Database\Seeders;

use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\PaymentStatus;
use App\Enums\Status\ProjectDtStatus;
use App\Enums\Status\ProjectStatus;
use App\Enums\Status\QuotationStatus;
use App\Enums\Status\TermStatus;
use App\Models\MsStatus;

class StatusSeeder extends SanitraSeeder
{
    public function run(): void
    {
        foreach ([
            QuotationStatus::class,
            ProjectStatus::class,
            ProjectDtStatus::class,
            TermStatus::class,
            InvoiceStatus::class,
            PaymentStatus::class,
        ] as $statusClass) {
            foreach ($statusClass::cases() as $status) {
                $this->uncoded(MsStatus::class,
                    ['type' => $statusClass::type()->value, 'code' => $status->value],
                    ['name' => $status->label(), 'active' => true]);
            }
        }
    }
}

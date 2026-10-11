<?php

namespace Database\Seeders;

use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\OrderPaymentStatus;
use App\Enums\Status\OrderStatus;
use App\Enums\Status\PaymentStatus;
use App\Enums\Status\ProjectStageStatus;
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
            OrderStatus::class,
            OrderPaymentStatus::class,
            ProjectStatus::class,
            ProjectStageStatus::class,
            TermStatus::class,
            InvoiceStatus::class,
            PaymentStatus::class,
        ] as $statusClass) {
            $codes = array_map(static fn ($status): string => $status->value, $statusClass::cases());

            foreach ($statusClass::cases() as $status) {
                MsStatus::withTrashed()->updateOrCreate(
                    ['type' => $statusClass::type()->value, 'code' => $status->value],
                    ['name' => $status->label(), 'active' => true, 'deleted_at' => null],
                );
            }

            foreach (MsStatus::query()->where('type', $statusClass::type()->value)->whereNotIn('code', $codes)->get() as $obsoleteStatus) {
                $obsoleteStatus->update(['active' => false]);
            }
        }
    }
}

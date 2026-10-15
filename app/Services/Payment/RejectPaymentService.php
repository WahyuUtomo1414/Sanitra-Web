<?php

namespace App\Services\Payment;

use App\Enums\Status\PaymentStatus;
use App\Models\MsStatus;
use App\Models\TransSalesPayment;
use DomainException;
use Illuminate\Support\Facades\DB;

class RejectPaymentService
{
    public function execute(TransSalesPayment $payment, string $reason): TransSalesPayment
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($payment, $reason): TransSalesPayment {
            $payment = TransSalesPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status?->code !== PaymentStatus::Pending->value) {
                throw new DomainException('Hanya pembayaran yang menunggu verifikasi dapat ditolak.');
            }

            $notes = trim(($payment->notes ? $payment->notes."\n\n" : '').'Alasan penolakan: '.$reason);
            $payment->update([
                'status_id' => MsStatus::query()
                    ->where('type', PaymentStatus::type()->value)
                    ->where('code', PaymentStatus::Rejected->value)
                    ->where('active', true)
                    ->firstOrFail()->id,
                'notes' => $notes,
            ]);

            return $payment->refresh();
        });
    }
}

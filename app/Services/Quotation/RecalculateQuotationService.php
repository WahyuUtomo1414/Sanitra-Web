<?php

namespace App\Services\Quotation;

use App\Enums\VatType;
use App\Models\TransSalesQuotationHd;
use DomainException;

class RecalculateQuotationService
{
    public function totals(TransSalesQuotationHd $quotation): TransSalesQuotationHd
    {
        $subtotal = $quotation->items()->sum('total_price');
        $discountTotal = $quotation->items()->sum('discount');
        $vatType = $quotation->vat_type;
        $vatRate = (string) config('sanitra.vat_rate');

        if ($vatType !== VatType::None
            && (! is_numeric($vatRate) || bccomp($vatRate, '0', 6) <= 0)) {
            throw new DomainException('Tarif PPN belum dikonfigurasi.');
        }

        $vatAmount = match ($vatType) {
            VatType::None => '0.000000',
            VatType::Include => bcdiv(bcmul((string) $subtotal, $vatRate, 6), bcadd('1', $vatRate, 6), 6),
            VatType::Exclude => bcmul((string) $subtotal, $vatRate, 6),
        };

        $quotation->update([
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'vat_amount' => $vatAmount,
            'grand_total' => $vatType === VatType::Exclude
                ? bcadd((string) $subtotal, $vatAmount, 6)
                : $subtotal,
        ]);

        $quotation->refresh();
        $this->termAmounts($quotation);

        return $quotation;
    }

    public function termAmounts(TransSalesQuotationHd $quotation): void
    {
        $terms = $quotation->terms()->orderBy('sequence')->get();
        $hasCompleteAllocation = $terms->sum('percentage') === 100;
        $lastTermId = $hasCompleteAllocation ? $terms->last()?->id : null;
        $allocated = '0.000000';
        $grandTotal = (string) $quotation->grand_total;

        foreach ($terms as $term) {
            $amount = $term->id === $lastTermId
                ? bcsub($grandTotal, $allocated, 6)
                : bcdiv(bcmul($grandTotal, (string) $term->percentage, 6), '100', 6);

            $term->update(['amount' => $amount]);
            $allocated = bcadd($allocated, $amount, 6);
        }
    }
}

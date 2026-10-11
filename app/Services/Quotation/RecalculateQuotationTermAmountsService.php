<?php

namespace App\Services\Quotation;

use App\Models\TransSalesQuotationHd;

class RecalculateQuotationTermAmountsService
{
    public function execute(TransSalesQuotationHd $quotation): void
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

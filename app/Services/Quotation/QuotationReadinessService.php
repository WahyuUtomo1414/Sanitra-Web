<?php

namespace App\Services\Quotation;

use App\Models\TransSalesQuotationHd;
use DomainException;

class QuotationReadinessService
{
    public function missingRequirement(TransSalesQuotationHd $quotation): ?string
    {
        if (! $quotation->items()->exists()) {
            return 'Quotation harus memiliki minimal satu item.';
        }

        $terms = $quotation->terms()->get(['percentage', 'progress_threshold']);

        if ($terms->isEmpty() || (int) $terms->sum('percentage') !== 100) {
            return 'Total persentase termin harus 100%.';
        }

        if ($terms->where('progress_threshold', 0)->count() !== 1) {
            return 'Quotation harus memiliki tepat satu termin DP dengan threshold 0%.';
        }

        return null;
    }

    public function isReady(TransSalesQuotationHd $quotation): bool
    {
        return $this->missingRequirement($quotation) === null;
    }

    public function ensureReady(TransSalesQuotationHd $quotation): void
    {
        $missingRequirement = $this->missingRequirement($quotation);

        if ($missingRequirement !== null) {
            throw new DomainException($missingRequirement);
        }
    }
}

<?php

namespace App\Services\Quotation;

use App\Enums\VatType;
use App\Models\TransSalesQuotationHd;
use DomainException;

class RecalculateQuotationTotalsService
{
    public function __construct(public RecalculateQuotationTermAmountsService $recalculateTermAmounts) {}

    public function execute(TransSalesQuotationHd $quotation): TransSalesQuotationHd
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
        $this->recalculateTermAmounts->execute($quotation);

        return $quotation;
    }
}

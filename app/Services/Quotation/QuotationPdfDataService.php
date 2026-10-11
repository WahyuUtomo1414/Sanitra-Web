<?php

namespace App\Services\Quotation;

use App\Models\TransSalesQuotationHd;
use Illuminate\Support\Facades\Storage;

class QuotationPdfDataService
{
    /** @return array<string, mixed> */
    public function execute(TransSalesQuotationHd $quotation): array
    {
        $quotation->load(['company', 'client.picEmployee', 'items.item', 'items.unit', 'terms']);
        $terms = $quotation->terms->sortBy('sequence')->values();
        $termAmounts = [];
        $allocated = '0.000000';

        foreach ($terms as $term) {
            $amount = $term->is($terms->last())
                ? bcsub((string) $quotation->grand_total, $allocated, 6)
                : bcdiv(bcmul((string) $quotation->grand_total, (string) $term->percentage, 6), '100', 6);

            $termAmounts[$term->id] = $amount;
            $allocated = bcadd($allocated, $amount, 6);
        }

        $logoData = null;
        $logoPath = $quotation->company->logo;

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
            $logoData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
        }

        return [
            'quotation' => $quotation,
            'terms' => $terms,
            'termAmounts' => $termAmounts,
            'logoData' => $logoData,
            'rupiah' => static fn (string|int|float $amount): string => 'Rp '.number_format((float) $amount, 0, ',', '.'),
        ];
    }
}

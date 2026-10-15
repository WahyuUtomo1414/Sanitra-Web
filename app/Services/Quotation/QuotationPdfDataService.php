<?php

namespace App\Services\Quotation;

use App\Models\TransSalesQuotationHd;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Illuminate\Support\Facades\Storage;

class QuotationPdfDataService
{
    /** @return array<string, mixed> */
    public function execute(TransSalesQuotationHd $quotation): array
    {
        $quotation->load(['company', 'client.picEmployee', 'projectType', 'items.item', 'items.unit', 'terms']);
        $terms = $quotation->terms->sortBy('sequence')->values();
        $termAmounts = $terms->mapWithKeys(fn ($term): array => [$term->id => (string) $term->amount])->all();

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
            'angka' => static fn (string|int|float $amount): string => IndonesianMoneyFormatter::formatNumber($amount),
            'rupiah' => static fn (string|int|float $amount): string => IndonesianMoneyFormatter::formatRupiah($amount),
        ];
    }
}

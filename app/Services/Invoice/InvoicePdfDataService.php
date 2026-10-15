<?php

namespace App\Services\Invoice;

use App\Models\TransSalesInvoiceHd;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Illuminate\Support\Facades\Storage;

class InvoicePdfDataService
{
    /** @return array<string, mixed> */
    public function execute(TransSalesInvoiceHd $invoice): array
    {
        $invoice->load(['company', 'client', 'order', 'bankAccount', 'items.salesTerm']);

        $logoData = null;
        $logoPath = $invoice->company?->logo;

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
            $logoData = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
        }

        return [
            'invoice' => $invoice,
            'items' => $invoice->items->sortBy('sequence')->values(),
            'logoData' => $logoData,
            'rupiah' => static fn (string|int|float|null $amount): string => IndonesianMoneyFormatter::formatRupiah($amount),
        ];
    }
}

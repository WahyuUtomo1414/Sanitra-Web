<?php

namespace App\Services\Quotation;

use App\Enums\Status\QuotationStatus;
use App\Models\MsItemUnit;
use App\Models\TransSalesQuotationDt;
use App\Models\TransSalesQuotationHd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManageQuotationItemsService
{
    public function __construct(public RecalculateQuotationTotalsService $recalculateTotals) {}

    /** @param array<string, mixed> $data */
    public function save(TransSalesQuotationHd $quotation, array $data, ?TransSalesQuotationDt $item = null): TransSalesQuotationDt
    {
        $data = Validator::make($data, [
            'item_id' => ['required', 'integer', 'exists:ms_item,id'],
            'unit_id' => ['required', 'integer', 'exists:ms_unit,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($quotation, $data, $item): TransSalesQuotationDt {
            $quotation = $this->editableQuotation($quotation);

            if ($item && $item->quotation_hd_id !== $quotation->id) {
                throw ValidationException::withMessages(['item_id' => 'Item tidak termasuk quotation ini.']);
            }

            if (! MsItemUnit::query()->where('item_id', $data['item_id'])
                ->where('unit_id', $data['unit_id'])->where('active', true)->exists()) {
                throw ValidationException::withMessages(['unit_id' => 'Unit tidak tersedia untuk item ini.']);
            }

            $gross = bcmul((string) $data['qty'], (string) $data['unit_price'], 6);

            if (bccomp((string) $data['discount'], $gross, 6) > 0) {
                throw ValidationException::withMessages(['discount' => 'Diskon tidak boleh melebihi harga baris.']);
            }

            $attributes = [
                'item_id' => $data['item_id'],
                'unit_id' => $data['unit_id'],
                'qty' => $data['qty'],
                'unit_price' => $data['unit_price'],
                'discount' => $data['discount'],
                'total_price' => bcsub($gross, (string) $data['discount'], 6),
                'notes' => $data['notes'] ?? null,
            ];

            if ($item) {
                $item->update($attributes);
            } else {
                $item = $quotation->items()->create($attributes);
            }

            $this->recalculateTotals->execute($quotation);

            return $item;
        });
    }

    public function delete(TransSalesQuotationHd $quotation, TransSalesQuotationDt $item): bool
    {
        return DB::transaction(function () use ($quotation, $item): bool {
            $quotation = $this->editableQuotation($quotation);

            if ($item->quotation_hd_id !== $quotation->id) {
                throw ValidationException::withMessages(['item_id' => 'Item tidak termasuk quotation ini.']);
            }

            $deleted = $item->delete();
            $this->recalculateTotals->execute($quotation);

            return $deleted;
        });
    }

    private function editableQuotation(TransSalesQuotationHd $quotation): TransSalesQuotationHd
    {
        $quotation = TransSalesQuotationHd::query()->lockForUpdate()->findOrFail($quotation->id);

        if ($quotation->status?->code !== QuotationStatus::Draft->value) {
            throw ValidationException::withMessages(['item_id' => 'Item hanya dapat diubah saat quotation Draft.']);
        }

        return $quotation;
    }
}

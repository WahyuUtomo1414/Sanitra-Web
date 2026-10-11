<?php

namespace App\Services\Quotation;

use App\Enums\Status\QuotationStatus;
use App\Enums\Status\TermStatus;
use App\Models\MsStatus;
use App\Models\TransSalesQuotationHd;
use App\Models\TransSalesTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManageQuotationTermsService
{
    /** @param array<string, mixed> $data */
    public function save(TransSalesQuotationHd $quotation, array $data, ?TransSalesTerm $term = null): TransSalesTerm
    {
        $data = Validator::make($data, [
            'sequence' => ['required', 'integer', 'min:1'],
            'term_name' => ['required', 'string', 'max:128'],
            'percentage' => ['required', 'integer', 'between:1,100'],
            'progress_threshold' => ['required', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($quotation, $data, $term): TransSalesTerm {
            $quotation = $this->editableQuotation($quotation);

            if ($term && $term->quotation_hd_id !== $quotation->id) {
                throw ValidationException::withMessages(['term_name' => 'Termin tidak termasuk quotation ini.']);
            }

            $siblings = $quotation->terms()->when($term, fn ($query) => $query->whereKeyNot($term->id));

            if ((clone $siblings)->where('sequence', $data['sequence'])->exists()) {
                throw ValidationException::withMessages(['sequence' => 'Urutan termin sudah digunakan.']);
            }

            if ((int) $data['progress_threshold'] === 0
                && (clone $siblings)->where('progress_threshold', 0)->exists()) {
                throw ValidationException::withMessages(['progress_threshold' => 'Hanya satu termin DP yang diizinkan.']);
            }

            if ((int) $data['percentage'] + (int) $siblings->sum('percentage') > 100) {
                throw ValidationException::withMessages(['percentage' => 'Total persentase termin tidak boleh melebihi 100%.']);
            }

            $attributes = [
                'sequence' => $data['sequence'],
                'term_name' => $data['term_name'],
                'percentage' => $data['percentage'],
                'progress_threshold' => $data['progress_threshold'],
                'notes' => $data['notes'] ?? null,
            ];

            if ($term) {
                $term->update($attributes);

                return $term;
            }

            $deletedTerm = $quotation->terms()->withTrashed()
                ->where('sequence', $data['sequence'])->whereNotNull('deleted_at')->first();

            if ($deletedTerm) {
                $deletedTerm->restore();
                $deletedTerm->update([
                    ...$attributes,
                    'amount' => 0,
                    'status_id' => MsStatus::query()->where('type', TermStatus::type()->value)
                        ->where('code', TermStatus::Draft->value)->where('active', true)->firstOrFail()->id,
                ]);

                return $deletedTerm;
            }

            return $quotation->terms()->create([
                ...$attributes,
                'amount' => 0,
                'status_id' => MsStatus::query()->where('type', TermStatus::type()->value)
                    ->where('code', TermStatus::Draft->value)->where('active', true)->firstOrFail()->id,
            ]);
        });
    }

    public function delete(TransSalesQuotationHd $quotation, TransSalesTerm $term): bool
    {
        return DB::transaction(function () use ($quotation, $term): bool {
            $quotation = $this->editableQuotation($quotation);

            if ($term->quotation_hd_id !== $quotation->id) {
                throw ValidationException::withMessages(['term_name' => 'Termin tidak termasuk quotation ini.']);
            }

            return $term->delete();
        });
    }

    private function editableQuotation(TransSalesQuotationHd $quotation): TransSalesQuotationHd
    {
        $quotation = TransSalesQuotationHd::query()->lockForUpdate()->findOrFail($quotation->id);

        if ($quotation->status?->code !== QuotationStatus::Draft->value) {
            throw ValidationException::withMessages(['term_name' => 'Termin hanya dapat diubah saat quotation Draft.']);
        }

        return $quotation;
    }
}

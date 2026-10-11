<?php

namespace Database\Factories;

use App\Models\MsStatus;
use App\Models\TransSalesQuotationHd;
use App\Models\TransSalesTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransSalesTerm>
 */
class TransSalesTermFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quotation_hd_id' => TransSalesQuotationHd::factory(),
            'sequence' => 1,
            'term_name' => 'Termin 1',
            'percentage' => 100,
            'amount' => 0,
            'progress_threshold' => 100,
            'status_id' => MsStatus::query()->where('type', 'term')->where('code', 'draft')->firstOrFail()->id,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\MsItem;
use App\Models\MsUnit;
use App\Models\TransSalesQuotationDt;
use App\Models\TransSalesQuotationHd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransSalesQuotationDt>
 */
class TransSalesQuotationDtFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quotation_hd_id' => TransSalesQuotationHd::factory(),
            'item_id' => MsItem::query()->firstOrFail()->id,
            'unit_id' => MsUnit::query()->firstOrFail()->id,
            'qty' => 1,
            'unit_price' => 1000000,
            'discount' => 0,
            'total_price' => 1000000,
        ];
    }
}

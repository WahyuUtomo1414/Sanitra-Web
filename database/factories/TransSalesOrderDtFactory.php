<?php

namespace Database\Factories;

use App\Models\MsItem;
use App\Models\MsUnit;
use App\Models\TransSalesOrderDt;
use App\Models\TransSalesOrderHd;
use App\Models\TransSalesQuotationDt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransSalesOrderDt>
 */
class TransSalesOrderDtFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_hd_id' => TransSalesOrderHd::factory(),
            'quotation_dt_id' => TransSalesQuotationDt::factory(),
            'item_id' => MsItem::query()->firstOrFail()->id,
            'unit_id' => MsUnit::query()->firstOrFail()->id,
            'qty' => 1,
            'unit_price' => 1000000,
            'discount' => 0,
            'total_price' => 1000000,
        ];
    }
}

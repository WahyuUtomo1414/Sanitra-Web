<?php

namespace Database\Factories;

use App\Models\TransSalesInvoiceDt;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransSalesInvoiceDt>
 */
class TransSalesInvoiceDtFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_hd_id' => TransSalesInvoiceHd::factory(),
            'sales_term_id' => TransSalesTerm::factory(),
            'sequence' => 1,
            'percentage' => 100,
            'amount' => 1000000,
        ];
    }
}

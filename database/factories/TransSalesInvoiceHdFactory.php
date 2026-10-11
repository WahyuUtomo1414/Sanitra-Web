<?php

namespace Database\Factories;

use App\Models\MsBankAccount;
use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsStatus;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesOrderHd;
use App\Models\TransSalesQuotationHd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransSalesInvoiceHd>
 */
class TransSalesInvoiceHdFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('INV/'.now()->format('y/m').'/####'),
            'transaction_date' => now()->toDateString(),
            'order_hd_id' => TransSalesOrderHd::factory(),
            'quotation_hd_id' => TransSalesQuotationHd::factory(),
            'client_id' => MsClient::query()->firstOrFail()->id,
            'company_id' => MsCompany::query()->firstOrFail()->id,
            'bank_account_id' => MsBankAccount::query()->firstOrFail()->id,
            'due_date' => now()->addDays(14)->toDateString(),
            'subtotal' => 1000000,
            'vat_type' => 'none',
            'vat_amount' => 0,
            'grand_total' => 1000000,
            'status_id' => MsStatus::query()->where('type', 'invoice')->where('code', 'unpaid')->firstOrFail()->id,
        ];
    }
}

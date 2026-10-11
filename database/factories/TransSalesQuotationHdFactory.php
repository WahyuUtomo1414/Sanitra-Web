<?php

namespace Database\Factories;

use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsProjectType;
use App\Models\MsStatus;
use App\Models\TransSalesQuotationHd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransSalesQuotationHd>
 */
class TransSalesQuotationHdFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('QUO/'.now()->format('y/m').'/####'),
            'transaction_date' => now()->toDateString(),
            'client_id' => MsClient::query()->firstOrFail()->id,
            'project_type_id' => MsProjectType::query()->firstOrFail()->id,
            'company_id' => MsCompany::query()->firstOrFail()->id,
            'payment_type' => 'Termin',
            'vat_type' => 'none',
            'vat_amount' => 0,
            'discount_total' => 0,
            'subtotal' => 1000000,
            'grand_total' => 1000000,
            'status_id' => MsStatus::query()->where('type', 'quotation')->where('code', 'draft')->firstOrFail()->id,
        ];
    }
}

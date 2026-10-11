<?php

namespace Database\Factories;

use App\Models\MsStatus;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransSalesPayment>
 */
class TransSalesPaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('PAY/'.now()->format('y/m').'/####'),
            'transaction_date' => now()->toDateString(),
            'invoice_hd_id' => TransSalesInvoiceHd::factory(),
            'amount' => 1000000,
            'payment_method' => 'transfer',
            'status_id' => MsStatus::query()->where('type', 'payment')->where('code', 'pending')->firstOrFail()->id,
        ];
    }
}

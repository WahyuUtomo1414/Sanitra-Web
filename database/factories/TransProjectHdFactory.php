<?php

namespace Database\Factories;

use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsEmployee;
use App\Models\MsProjectType;
use App\Models\MsStatus;
use App\Models\TransProjectHd;
use App\Models\TransSalesOrderHd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransProjectHd>
 */
class TransProjectHdFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('PRO/'.now()->format('y/m').'/####'),
            'transaction_date' => now()->toDateString(),
            'order_hd_id' => TransSalesOrderHd::factory(),
            'client_id' => MsClient::query()->firstOrFail()->id,
            'company_id' => MsCompany::query()->firstOrFail()->id,
            'project_type_id' => MsProjectType::query()->firstOrFail()->id,
            'pic_employee_id' => MsEmployee::query()->firstOrFail()->id,
            'project_name' => fake()->sentence(3),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'progress_percentage' => 0,
            'status_id' => MsStatus::query()->where('type', 'project')->where('code', 'open')->firstOrFail()->id,
        ];
    }
}

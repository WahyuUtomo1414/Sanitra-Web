<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Enums\EmployeePosition;
use App\Models\MsCompany;
use App\Models\MsEmployee;

class EmployeeSeeder extends SanitraSeeder
{
    public function run(): void
    {
        $company = MsCompany::findOrFail(1);

        foreach ([
            ['Staf Operasional (Contoh)', EmployeePosition::Teknis, 'Operasional', 'operasional@sanitra.invalid'],
            ['Staf Penjualan (Contoh)', EmployeePosition::Marketing, 'Penjualan', 'penjualan@sanitra.invalid'],
        ] as [$name, $position, $legacyPosition, $email]) {
            $employee = $this->coded(MsEmployee::class, AutoCodePrefix::MsEmployee, ['email' => $email], [
                'name' => $name,
                'position' => $position->value,
                'company_id' => $company->id,
                'phone' => '-',
                'active' => false,
            ]);

            if ($employee->position === $legacyPosition) {
                $employee->update(['position' => $position->value]);
            }
        }
    }
}

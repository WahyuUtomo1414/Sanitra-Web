<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Enums\EmployeePosition;
use App\Models\MsCompany;
use App\Models\MsEmployee;

class EmployeeSeeder extends SanitraSeeder
{
    private const OBSOLETE_EMAILS = [
        'operasional@sanitra.invalid',
        'penjualan@sanitra.invalid',
    ];

    public function run(): void
    {
        $company = MsCompany::findOrFail(1);

        MsEmployee::withTrashed()
            ->whereIn('email', self::OBSOLETE_EMAILS)
            ->get()
            ->each(fn (MsEmployee $employee) => $employee->forceDelete());

        foreach ([
            ['super@gmail.com', 'Super Admin', EmployeePosition::Owner],
            ['marketing@gmail.com', 'Marketing', EmployeePosition::Marketing],
            ['teknis@gmail.com', 'Teknis', EmployeePosition::Teknis],
            ['finance@gmail.com', 'Finance', EmployeePosition::Finance],
        ] as [$email, $name, $position]) {
            $employee = $this->coded(MsEmployee::class, AutoCodePrefix::MsEmployee, ['email' => $email], [
                'name' => $name,
                'position' => $position->value,
                'company_id' => $company->id,
                'phone' => '-',
                'active' => true,
            ]);

            if ($employee->trashed()) {
                $employee->restore();
            }

            $employee->update([
                'name' => $name,
                'position' => $position->value,
                'company_id' => $company->id,
                'active' => true,
            ]);
        }
    }
}

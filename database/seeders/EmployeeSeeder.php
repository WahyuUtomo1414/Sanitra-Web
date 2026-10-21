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
            ['Budi Santoso', EmployeePosition::Owner],
            ['Rina Wijaya', EmployeePosition::Marketing],
            ['Ahmad Fauzi', EmployeePosition::Teknis],
            ['Dewi Rahayu', EmployeePosition::Finance],
        ] as [$name, $position]) {
            $employee = $this->coded(MsEmployee::class, AutoCodePrefix::MsEmployee,
                ['position' => $position->value, 'company_id' => $company->id],
                [
                    'name' => $name,
                    'email' => 'wahyuxd14@gmail.com',
                    'phone' => '-',
                    'active' => true,
                ]);

        }
    }
}

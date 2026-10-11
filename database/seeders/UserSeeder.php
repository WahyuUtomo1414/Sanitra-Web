<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Enums\EmployeePosition;
use App\Models\MsCompany;
use App\Models\MsEmployee;
use App\Models\User;

class UserSeeder extends SanitraSeeder
{
    public function run(): void
    {
        $company = MsCompany::findOrFail(1);

        foreach ([
            ['super@gmail.com', 'Super Admin', EmployeePosition::Owner],
            ['marketing@gmail.com', 'Marketing', EmployeePosition::Marketing],
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
                'position' => $position->value,
                'company_id' => $company->id,
                'active' => true,
            ]);

            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => '12345678'],
            );

            if ($user->employee_id !== $employee->id) {
                $user->employee()->associate($employee);
                $user->save();
            }
        }
    }
}

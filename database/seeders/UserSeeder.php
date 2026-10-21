<?php

namespace Database\Seeders;

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
            ['super@gmail.com', 'Super Admin', EmployeePosition::Owner, null],
            ['marketing@gmail.com', 'Marketing', EmployeePosition::Marketing, 'Marketing'],
            ['teknis@gmail.com', 'Teknis', EmployeePosition::Teknis, 'Teknis'],
            ['finance@gmail.com', 'Finance', EmployeePosition::Finance, 'Finance'],
        ] as [$email, $name, $position, $role]) {
            $employee = MsEmployee::where('position', $position->value)
                ->where('company_id', $company->id)
                ->firstOrFail();

            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => '12345678'],
            );

            if ($user->employee_id !== $employee->id) {
                $user->employee()->associate($employee);
                $user->save();
            }

            if ($role !== null) {
                $user->syncRoles([$role]);
            }
        }
    }
}

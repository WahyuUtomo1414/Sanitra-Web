<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    private const ROLES = ['Marketing', 'Finance', 'Teknis'];

    public function run(): void
    {
        foreach (self::ROLES as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}

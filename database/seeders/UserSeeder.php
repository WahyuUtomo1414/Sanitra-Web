<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['super@gmail.com', 'Super Admin'],
            ['marketing@gmail.com', 'Marketing'],
            ['finance@gmail.com', 'Finance'],
        ] as [$email, $name]) {
            User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => '12345678'],
            );
        }
    }
}

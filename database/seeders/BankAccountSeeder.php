<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Models\MsBankAccount;

class BankAccountSeeder extends SanitraSeeder
{
    public function run(): void
    {
        $this->coded(MsBankAccount::class, AutoCodePrefix::MsBankAccount,
            ['name' => 'REKENING CONTOH - JANGAN DIGUNAKAN'],
            [
                'bank_name' => 'BELUM DIVERIFIKASI',
                'account_number' => '0000000000',
                'account_name' => 'PT Sanitra Sapta Utama',
                'description' => 'Placeholder nonaktif. Ganti dengan rekening resmi setelah verifikasi internal; jangan gunakan untuk pembayaran.',
                'active' => false,
            ]);
    }
}

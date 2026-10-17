<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Models\MsBankAccount;

class BankAccountSeeder extends SanitraSeeder
{
    private const ACCOUNTS = [
        [
            'name' => 'BCA - Operasional',
            'bank_name' => 'Bank Central Asia (BCA)',
            'account_number' => '035-1234-567',
            'account_name' => 'PT Sanitra Sapta Utama',
        ],
        [
            'name' => 'Mandiri - Operasional',
            'bank_name' => 'Bank Mandiri',
            'account_number' => '123-000-1234567',
            'account_name' => 'PT Sanitra Sapta Utama',
        ],
        [
            'name' => 'BNI - Operasional',
            'bank_name' => 'Bank Negara Indonesia (BNI)',
            'account_number' => '001-2345678',
            'account_name' => 'PT Sanitra Sapta Utama',
        ],
    ];

    public function run(): void
    {
        MsBankAccount::withTrashed()
            ->where('name', 'REKENING CONTOH - JANGAN DIGUNAKAN')
            ->get()
            ->each(fn (MsBankAccount $account) => $account->forceDelete());

        foreach (self::ACCOUNTS as $data) {
            $this->coded(MsBankAccount::class, AutoCodePrefix::MsBankAccount,
                ['name' => $data['name']],
                [
                    'bank_name' => $data['bank_name'],
                    'account_number' => $data['account_number'],
                    'account_name' => $data['account_name'],
                    'active' => true,
                ]);
        }
    }
}

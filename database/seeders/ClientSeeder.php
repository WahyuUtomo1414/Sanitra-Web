<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Models\MsClient;
use Illuminate\Support\Str;

class ClientSeeder extends SanitraSeeder
{
    public function run(): void
    {
        foreach ([
            'PT MRT Jakarta',
            'PT Pertamina Lubricants',
            'PT Bintang Toedjoe',
            'PT Toyota Astra Motor',
            'RSUD Tugu Koja',
            'RSUD Cilincing',
            'PT Panca Permata Pejaten',
        ] as $name) {
            $this->coded(MsClient::class, AutoCodePrefix::MsClient, ['name' => $name], [
                'email' => Str::slug($name).'@example.invalid',
                'phone' => '-',
                'address' => 'Belum diverifikasi',
                'description' => 'Organisasi tercantum dalam portofolio Sanitra. Kontak dan alamat placeholder; verifikasi sebelum transaksi.',
                'active' => false,
            ]);
        }
    }
}

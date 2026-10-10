<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Models\MsCompany;

class CompanySeeder extends SanitraSeeder
{
    private const SANITRA_ADDRESS = 'Ruko Multiguna Blok B3A No. 8H, Jl. Bintaro Utama 3A, Pondok Karya, Kec. Pondok Aren, Kota Tangerang Selatan';

    private const SANITRA_POSTAL_CODE = '15225';

    public function run(): void
    {
        $company = MsCompany::withTrashed()->find(1);

        if ($company !== null) {
            if ($company->legal_name !== 'PT Sanitra Sapta Utama' || $company->trashed() || ! $company->active || $company->parent_id !== null) {
                throw new \RuntimeException('ID perusahaan 1 harus ditempati PT Sanitra Sapta Utama yang aktif tanpa induk.');
            }

            if (blank($company->address)) {
                $company->address = self::SANITRA_ADDRESS;
            }

            if (blank($company->postal_code)) {
                $company->postal_code = self::SANITRA_POSTAL_CODE;
            }

            if ($company->isDirty()) {
                $company->save();
            }

            return;
        }

        if (MsCompany::withTrashed()->where('legal_name', 'PT Sanitra Sapta Utama')->exists()) {
            throw new \RuntimeException('PT Sanitra Sapta Utama sudah ada dengan ID selain 1.');
        }

        $company = new MsCompany([
            'code' => $this->codeService->next(AutoCodePrefix::MsCompany),
            'name' => 'Sanitra Sapta Utama',
            'legal_name' => 'PT Sanitra Sapta Utama',
            'email' => 'info@sanitra.co.id',
            'phone' => '02139701224',
            'address' => self::SANITRA_ADDRESS,
            'postal_code' => self::SANITRA_POSTAL_CODE,
            'description' => 'Jasa IPAL, perizinan lingkungan, TPS limbah B3, pest control, dan bahan penunjang IPAL.',
            'active' => true,
        ]);
        $company->id = 1;
        $company->save();
    }
}

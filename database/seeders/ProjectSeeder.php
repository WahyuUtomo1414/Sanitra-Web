<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Models\MsCompany;
use App\Models\MsProjectType;
use App\Models\MsProjectWorkflow;

class ProjectSeeder extends SanitraSeeder
{
    private const OBSOLETE_TYPES = [
        'Instalasi Pengolahan Air Limbah (IPAL)',
        'Perizinan Lingkungan',
        'TPS Limbah B3',
        'Pest Control Gedung',
        'Pengadaan Bahan dan Peralatan IPAL',
        'Kontraktor',
        'Service dan Maintenance',
    ];

    private const TYPES = [
        'Konsultan' => [
            ['Pengumpulan data / Pemberkasan data', 10],
            ['Public Hearing', 20],
            ['Penyusunan draft', 20],
            ['Pemeriksaan draft', 20],
            ['Submit ke Dinas Administrasi', 10],
            ['Sidang Perbaikan', 10],
            ['Penyerahan Akhir', 10],
        ],
    ];

    public function run(): void
    {
        $company = MsCompany::findOrFail(1);

        foreach (MsProjectType::withTrashed()->where('company_id', $company->id)->whereIn('name', self::OBSOLETE_TYPES)->get() as $obsoleteType) {
            $obsoleteType->workflows()->withTrashed()->forceDelete();
            $obsoleteType->forceDelete();
        }

        foreach (self::TYPES as $name => $steps) {
            $type = $this->coded(MsProjectType::class, AutoCodePrefix::MsProjectType,
                ['name' => $name, 'company_id' => $company->id],
                ['active' => true]);

            foreach ($steps as $index => [$stepName, $weight]) {
                $this->uncoded(MsProjectWorkflow::class,
                    ['type_id' => $type->id, 'sequence' => $index + 1],
                    ['name' => $stepName, 'description' => 'Contoh alur kerja; sesuaikan dengan SOP proyek.', 'progress_weight' => $weight, 'active' => true]);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\PaymentStatus;
use App\Enums\Status\ProjectDtStatus;
use App\Enums\Status\ProjectStatus;
use App\Enums\Status\QuotationStatus;
use App\Enums\Status\TermStatus;
use App\Enums\UnitType;
use App\Models\CodeSequence;
use App\Models\MsBankAccount;
use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsEmployee;
use App\Models\MsItem;
use App\Models\MsItemCategory;
use App\Models\MsItemUnit;
use App\Models\MsProjectType;
use App\Models\MsProjectWorkflow;
use App\Models\MsStatus;
use App\Models\MsUnit;
use App\Models\User;
use App\Services\Shared\GenerateCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SanitraMasterSeeder extends Seeder
{
    private const SANITRA_ADDRESS = 'Ruko Multiguna Blok B3A No. 8H, Jl. Bintaro Utama 3A, Pondok Karya, Kec. Pondok Aren, Kota Tangerang Selatan';

    private const SANITRA_POSTAL_CODE = '15225';

    public function __construct(private readonly GenerateCodeService $codeService) {}

    public function run(): void
    {
        $previousUser = Auth::user();

        try {
            DB::transaction(function (): void {
                Auth::setUser(User::query()->firstOrCreate(
                    ['email' => 'master-seeder@sanitra.invalid'],
                    ['name' => 'Seeder Master Sanitra', 'password' => Str::random(64)],
                ));

                $this->synchronizeSequences();
                $company = $this->seedCompany();
                $this->seedEmployees($company);
                $this->seedClients();
                $this->seedProjectTypes($company);
                $categories = $this->seedCategories();
                $units = $this->seedUnits();
                $this->seedItems($categories, $units);
                $this->seedBankAccount();
                $this->seedStatuses();
            });
        } finally {
            if ($previousUser !== null) {
                Auth::setUser($previousUser);
            } else {
                Auth::forgetGuards();
            }
        }
    }

    private function seedCompany(): MsCompany
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

            return $company;
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

        return $company;
    }

    private function seedEmployees(MsCompany $company): void
    {
        foreach ([['Staf Operasional (Contoh)', 'Operasional', 'operasional@sanitra.invalid'],
            ['Staf Penjualan (Contoh)', 'Penjualan', 'penjualan@sanitra.invalid']] as [$name, $position, $email]) {
            $this->coded(MsEmployee::class, AutoCodePrefix::MsEmployee, ['email' => $email],
                ['name' => $name, 'position' => $position, 'company_id' => $company->id, 'phone' => '-', 'active' => false]);
        }
    }

    private function seedClients(): void
    {
        foreach (['PT MRT Jakarta', 'PT Pertamina Lubricants', 'PT Bintang Toedjoe',
            'PT Toyota Astra Motor', 'RSUD Tugu Koja', 'RSUD Cilincing', 'PT Panca Permata Pejaten'] as $name) {
            $this->coded(MsClient::class, AutoCodePrefix::MsClient, ['name' => $name],
                ['email' => Str::slug($name).'@example.invalid', 'phone' => '-', 'address' => 'Belum diverifikasi',
                    'description' => 'Organisasi tercantum dalam portofolio Sanitra. Kontak dan alamat placeholder; verifikasi sebelum transaksi.',
                    'active' => false]);
        }
    }

    private function seedProjectTypes(MsCompany $company): void
    {
        $types = [
            'Instalasi Pengolahan Air Limbah (IPAL)' => [['Survei kebutuhan', 10], ['Desain teknis (DED)', 25], ['Konstruksi dan instalasi', 40], ['Uji fungsi', 15], ['Serah terima', 10]],
            'Perizinan Lingkungan' => [['Pengumpulan dokumen', 20], ['Penyusunan dokumen', 40], ['Pengajuan izin', 20], ['Finalisasi', 20]],
            'TPS Limbah B3' => [['Survei lokasi', 15], ['Desain TPS', 20], ['Konstruksi', 45], ['Pemeriksaan', 10], ['Serah terima', 10]],
            'Pest Control Gedung' => [['Inspeksi lokasi', 20], ['Tindakan pengendalian', 50], ['Pemantauan', 20], ['Laporan', 10]],
            'Pengadaan Bahan dan Peralatan IPAL' => [['Kebutuhan teknis', 20], ['Pengadaan', 40], ['Pengiriman', 30], ['Serah terima', 10]],
        ];

        foreach ($types as $name => $steps) {
            $type = $this->coded(MsProjectType::class, AutoCodePrefix::MsProjectType,
                ['name' => $name, 'company_id' => $company->id], ['active' => true]);

            foreach ($steps as $index => [$stepName, $weight]) {
                $this->uncoded(MsProjectWorkflow::class, ['type_id' => $type->id, 'sequence' => $index + 1],
                    ['name' => $stepName, 'description' => 'Contoh alur kerja; sesuaikan dengan SOP proyek.',
                        'progress_weight' => $weight, 'active' => true]);
            }
        }
    }

    /** @return array<string, MsItemCategory> */
    private function seedCategories(): array
    {
        $categories = [];
        foreach (['Bakteri Pengurai', 'Bahan Kimia IPAL', 'Peralatan Penunjang IPAL',
            'Jasa IPAL', 'Jasa Perizinan Lingkungan', 'Jasa Pest Control'] as $name) {
            $categories[$name] = $this->coded(MsItemCategory::class, AutoCodePrefix::MsItemCategory,
                ['name' => $name], ['description' => 'Kategori berdasarkan layanan dan produk yang ditampilkan Sanitra.', 'active' => true]);
        }

        return $categories;
    }

    /** @return array<string, MsUnit> */
    private function seedUnits(): array
    {
        $units = [];
        foreach ([['JAM', 'Jam', UnitType::Waktu], ['HARI', 'Hari', UnitType::Waktu], ['BLN', 'Bulan', UnitType::Waktu],
            ['UNIT', 'Unit', UnitType::Barang], ['LTR', 'Liter', UnitType::Barang], ['KG', 'Kilogram', UnitType::Barang],
            ['PKT', 'Paket', UnitType::Barang]] as [$code, $name, $type]) {
            $units[$code] = $this->uncoded(MsUnit::class, ['code' => $code],
                ['name' => $name, 'type' => $type->value, 'active' => true]);
        }

        return $units;
    }

    /**
     * @param  array<string, MsItemCategory>  $categories
     * @param  array<string, MsUnit>  $units
     */
    private function seedItems(array $categories, array $units): void
    {
        foreach ([['Bakteri Pengurai IPAL', 'Bakteri Pengurai', 'KG'],
            ['Bahan Kimia Disinfeksi IPAL', 'Bahan Kimia IPAL', 'LTR'],
            ['Pompa Dosing IPAL', 'Peralatan Penunjang IPAL', 'UNIT'],
            ['Jasa Survei IPAL', 'Jasa IPAL', 'HARI'],
            ['Jasa Desain Teknis IPAL', 'Jasa IPAL', 'PKT'],
            ['Jasa Pemeliharaan IPAL', 'Jasa IPAL', 'BLN'],
            ['Jasa Pengurusan Izin Lingkungan', 'Jasa Perizinan Lingkungan', 'PKT'],
            ['Jasa Pest Control Gedung', 'Jasa Pest Control', 'PKT']] as [$name, $categoryName, $unitCode]) {
            $item = $this->coded(MsItem::class, AutoCodePrefix::MsItem,
                ['name' => $name, 'item_category_id' => $categories[$categoryName]->id],
                ['description' => 'Contoh katalog berdasarkan layanan/produk Sanitra; bukan SKU atau harga resmi.', 'active' => true]);
            $this->uncoded(MsItemUnit::class, ['item_id' => $item->id, 'unit_id' => $units[$unitCode]->id],
                ['is_base' => true, 'conversion' => 1, 'active' => true]);
        }
    }

    private function seedBankAccount(): void
    {
        $this->coded(MsBankAccount::class, AutoCodePrefix::MsBankAccount,
            ['name' => 'REKENING CONTOH - JANGAN DIGUNAKAN'],
            ['bank_name' => 'BELUM DIVERIFIKASI', 'account_number' => '0000000000',
                'account_name' => 'PT Sanitra Sapta Utama',
                'description' => 'Placeholder nonaktif. Ganti dengan rekening resmi setelah verifikasi internal; jangan gunakan untuk pembayaran.',
                'active' => false]);
    }

    private function seedStatuses(): void
    {
        foreach ([QuotationStatus::class, ProjectStatus::class, ProjectDtStatus::class,
            TermStatus::class, InvoiceStatus::class, PaymentStatus::class] as $statusClass) {
            foreach ($statusClass::cases() as $status) {
                $this->uncoded(MsStatus::class,
                    ['type' => $statusClass::type()->value, 'code' => $status->value],
                    ['name' => $status->label(), 'active' => true]);
            }
        }
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $modelClass
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $attributes
     * @return T
     */
    private function coded(string $modelClass, AutoCodePrefix $prefix, array $identity, array $attributes): Model
    {
        return $modelClass::withTrashed()->where($identity)->first()
            ?? $modelClass::create([...$identity, ...$attributes, 'code' => $this->codeService->next($prefix)]);
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $modelClass
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $attributes
     * @return T
     */
    private function uncoded(string $modelClass, array $identity, array $attributes): Model
    {
        return $modelClass::withTrashed()->where($identity)->first()
            ?? $modelClass::create([...$identity, ...$attributes]);
    }

    private function synchronizeSequences(): void
    {
        foreach ([AutoCodePrefix::MsCompany->value => MsCompany::class,
            AutoCodePrefix::MsEmployee->value => MsEmployee::class,
            AutoCodePrefix::MsClient->value => MsClient::class,
            AutoCodePrefix::MsProjectType->value => MsProjectType::class,
            AutoCodePrefix::MsItemCategory->value => MsItemCategory::class,
            AutoCodePrefix::MsItem->value => MsItem::class,
            AutoCodePrefix::MsBankAccount->value => MsBankAccount::class] as $prefix => $modelClass) {
            $highestNumber = 0;
            foreach ($modelClass::withTrashed()->where('code', 'like', $prefix.'%')->pluck('code') as $code) {
                if (preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $code, $matches)) {
                    $highestNumber = max($highestNumber, (int) $matches[1]);
                }
            }

            if ($highestNumber === 0) {
                continue;
            }

            $sequence = CodeSequence::query()->where('prefix', $prefix)->whereNull('year')
                ->whereNull('month')->lockForUpdate()->first();

            if ($sequence === null) {
                CodeSequence::create(['prefix' => $prefix, 'year' => null, 'month' => null, 'last_number' => $highestNumber]);
            } elseif ($sequence->last_number < $highestNumber) {
                $sequence->update(['last_number' => $highestNumber]);
            }
        }
    }
}

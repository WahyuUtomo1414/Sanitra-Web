<?php

namespace App\Imports;

use App\Enums\AutoCodePrefix;
use App\Models\MsCompany;
use App\Models\MsEmployee;
use App\Services\Shared\GenerateCodeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class MsEmployeeImport implements ToCollection, WithHeadingRow, WithLimit
{
    public array $errors = [];

    public int $successCount = 0;

    private GenerateCodeService $codeService;

    public function __construct()
    {
        $this->codeService = app(GenerateCodeService::class);
    }

    public function limit(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $name = trim((string) ($row['nama'] ?? ''));
            $position = trim((string) ($row['jabatan'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $phone = trim((string) ($row['telepon'] ?? ''));
            $companyCode = trim((string) ($row['kode_perusahaan'] ?? ''));

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama' wajib diisi.";

                continue;
            }

            if ($position === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'jabatan' wajib diisi.";

                continue;
            }

            if ($email !== '' && MsEmployee::where('email', $email)->exists()) {
                $this->errors[] = "Baris {$rowNum}: email '{$email}' sudah digunakan.";

                continue;
            }

            $companyId = null;
            if ($companyCode !== '') {
                $company = MsCompany::where('code', $companyCode)->first();
                if (! $company) {
                    $this->errors[] = "Baris {$rowNum}: perusahaan dengan kode '{$companyCode}' tidak ditemukan.";

                    continue;
                }
                $companyId = $company->id;
            }

            $code = $this->codeService->next(AutoCodePrefix::MsEmployee);

            MsEmployee::create([
                'code' => $code,
                'name' => $name,
                'position' => $position,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'company_id' => $companyId,
                'active' => true,
            ]);

            $this->successCount++;
        }
    }
}

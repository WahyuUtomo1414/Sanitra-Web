<?php

namespace App\Imports;

use App\Enums\AutoCodePrefix;
use App\Enums\EmployeePosition;
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

            if (EmployeePosition::tryFrom($position) === null) {
                $this->errors[] = "Baris {$rowNum}: jabatan harus Owner, Marketing, Teknis, atau Finance.";

                continue;
            }

            if ($email === '' || $phone === '' || $companyCode === '') {
                $this->errors[] = "Baris {$rowNum}: email, telepon, dan kode perusahaan wajib diisi.";

                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($name) > 128 || mb_strlen($email) > 128 || mb_strlen($phone) > 18) {
                $this->errors[] = "Baris {$rowNum}: nama, email, atau telepon tidak valid.";

                continue;
            }

            if (MsEmployee::where('email', $email)->exists()) {
                $this->errors[] = "Baris {$rowNum}: email '{$email}' sudah digunakan.";

                continue;
            }

            $company = MsCompany::where('code', $companyCode)->first();
            if (! $company) {
                $this->errors[] = "Baris {$rowNum}: perusahaan dengan kode '{$companyCode}' tidak ditemukan.";

                continue;
            }

            $code = $this->codeService->next(AutoCodePrefix::MsEmployee);

            MsEmployee::create([
                'code' => $code,
                'name' => $name,
                'position' => $position,
                'email' => $email,
                'phone' => $phone,
                'company_id' => $company->id,
                'active' => true,
            ]);

            $this->successCount++;
        }
    }
}

<?php

namespace App\Imports;

use App\Enums\AutoCodePrefix;
use App\Models\MsCompany;
use App\Services\Shared\GenerateCodeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class MsCompanyImport implements ToCollection, WithHeadingRow, WithLimit
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
            $legalName = trim((string) ($row['nama_legal'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $phone = trim((string) ($row['telepon'] ?? ''));
            $description = trim((string) ($row['deskripsi'] ?? ''));

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama' wajib diisi.";

                continue;
            }

            if ($email !== '' && MsCompany::where('email', $email)->exists()) {
                $this->errors[] = "Baris {$rowNum}: email '{$email}' sudah digunakan.";

                continue;
            }

            $code = $this->codeService->next(AutoCodePrefix::MsCompany);

            MsCompany::create([
                'code' => $code,
                'name' => $name,
                'legal_name' => $legalName ?: null,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'description' => $description ?: null,
                'active' => true,
            ]);

            $this->successCount++;
        }
    }
}

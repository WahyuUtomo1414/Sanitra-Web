<?php

namespace App\Imports;

use App\Enums\AutoCodePrefix;
use App\Models\MsBankAccount;
use App\Services\Shared\GenerateCodeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class MsBankAccountImport implements ToCollection, WithHeadingRow, WithLimit
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
            $bankName = trim((string) ($row['nama_bank'] ?? ''));
            $accountNumber = trim((string) ($row['nomor_rekening'] ?? ''));
            $accountName = trim((string) ($row['nama_pemilik'] ?? ''));
            $description = trim((string) ($row['deskripsi'] ?? ''));

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama' wajib diisi.";

                continue;
            }

            if ($bankName === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama_bank' wajib diisi.";

                continue;
            }

            if ($accountNumber === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nomor_rekening' wajib diisi.";

                continue;
            }

            if ($accountName === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama_pemilik' wajib diisi.";

                continue;
            }

            if (MsBankAccount::where('account_number', $accountNumber)->exists()) {
                $this->errors[] = "Baris {$rowNum}: nomor rekening '{$accountNumber}' sudah digunakan.";

                continue;
            }

            $code = $this->codeService->next(AutoCodePrefix::MsBankAccount);

            MsBankAccount::create([
                'code' => $code,
                'name' => $name,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'description' => $description ?: null,
                'active' => true,
            ]);

            $this->successCount++;
        }
    }
}

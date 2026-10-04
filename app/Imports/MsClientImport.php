<?php

namespace App\Imports;

use App\Enums\AutoCodePrefix;
use App\Models\MsClient;
use App\Models\MsEmployee;
use App\Services\Shared\GenerateCodeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class MsClientImport implements ToCollection, WithHeadingRow, WithLimit
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
            $email = trim((string) ($row['email'] ?? ''));
            $phone = trim((string) ($row['telepon'] ?? ''));
            $address = trim((string) ($row['alamat'] ?? ''));
            $description = trim((string) ($row['deskripsi'] ?? ''));
            $picCode = trim((string) ($row['kode_pic'] ?? ''));

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama' wajib diisi.";

                continue;
            }

            if ($address === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'alamat' wajib diisi.";

                continue;
            }

            if ($email !== '' && MsClient::where('email', $email)->exists()) {
                $this->errors[] = "Baris {$rowNum}: email '{$email}' sudah digunakan.";

                continue;
            }

            $picEmployeeId = null;
            if ($picCode !== '') {
                $employee = MsEmployee::where('code', $picCode)->first();
                if (! $employee) {
                    $this->errors[] = "Baris {$rowNum}: karyawan PIC dengan kode '{$picCode}' tidak ditemukan.";

                    continue;
                }
                $picEmployeeId = $employee->id;
            }

            $code = $this->codeService->next(AutoCodePrefix::MsClient);

            MsClient::create([
                'code' => $code,
                'name' => $name,
                'email' => $email ?: null,
                'phone' => $phone ?: null,
                'address' => $address,
                'description' => $description ?: null,
                'pic_employee_id' => $picEmployeeId,
                'active' => true,
            ]);

            $this->successCount++;
        }
    }
}

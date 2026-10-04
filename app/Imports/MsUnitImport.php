<?php

namespace App\Imports;

use App\Enums\UnitType;
use App\Models\MsUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class MsUnitImport implements ToCollection, WithHeadingRow, WithLimit
{
    public array $errors = [];

    public int $successCount = 0;

    public function limit(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        $validTypes = array_column(UnitType::cases(), 'value');

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $code = trim((string) ($row['kode'] ?? ''));
            $name = trim((string) ($row['nama'] ?? ''));
            $type = trim((string) ($row['tipe'] ?? ''));
            $description = trim((string) ($row['deskripsi'] ?? ''));

            if ($code === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'kode' wajib diisi.";

                continue;
            }

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama' wajib diisi.";

                continue;
            }

            if (! in_array($type, $validTypes, true)) {
                $this->errors[] = "Baris {$rowNum}: tipe '{$type}' tidak valid. Gunakan: ".implode(', ', $validTypes).'.';

                continue;
            }

            if (MsUnit::where('code', $code)->exists()) {
                $this->errors[] = "Baris {$rowNum}: kode unit '{$code}' sudah digunakan.";

                continue;
            }

            MsUnit::create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'description' => $description ?: null,
                'active' => true,
            ]);

            $this->successCount++;
        }
    }
}

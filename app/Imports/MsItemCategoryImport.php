<?php

namespace App\Imports;

use App\Enums\AutoCodePrefix;
use App\Models\MsItemCategory;
use App\Services\Shared\GenerateCodeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class MsItemCategoryImport implements ToCollection, WithHeadingRow, WithLimit
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
            $description = trim((string) ($row['deskripsi'] ?? ''));

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama' wajib diisi.";

                continue;
            }

            if (MsItemCategory::where('name', $name)->exists()) {
                $this->errors[] = "Baris {$rowNum}: kategori item dengan nama '{$name}' sudah ada.";

                continue;
            }

            $code = $this->codeService->next(AutoCodePrefix::MsItemCategory);

            MsItemCategory::create([
                'code' => $code,
                'name' => $name,
                'description' => $description ?: null,
                'active' => true,
            ]);

            $this->successCount++;
        }
    }
}

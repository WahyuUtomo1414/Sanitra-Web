<?php

namespace App\Imports;

use App\Enums\AutoCodePrefix;
use App\Models\MsItem;
use App\Models\MsItemCategory;
use App\Services\Shared\GenerateCodeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class MsItemImport implements ToCollection, WithHeadingRow, WithLimit
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
            $categoryName = trim((string) ($row['kategori'] ?? ''));

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama' wajib diisi.";

                continue;
            }

            if ($categoryName === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'kategori' wajib diisi.";

                continue;
            }

            $category = MsItemCategory::where('name', $categoryName)->first();
            if (! $category) {
                $this->errors[] = "Baris {$rowNum}: kategori item '{$categoryName}' tidak ditemukan.";

                continue;
            }

            $code = $this->codeService->next(AutoCodePrefix::MsItem);

            MsItem::create([
                'code' => $code,
                'name' => $name,
                'description' => $description ?: null,
                'item_category_id' => $category->id,
                'active' => true,
            ]);

            $this->successCount++;
        }
    }
}

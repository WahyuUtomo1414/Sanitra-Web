<?php

namespace App\Imports;

use App\Enums\AutoCodePrefix;
use App\Models\MsItem;
use App\Models\MsItemCategory;
use App\Models\MsUnit;
use App\Services\Item\ManageItemUnitsService;
use App\Services\Shared\GenerateCodeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
            $baseUnitCode = trim((string) ($row['kode_unit_dasar'] ?? ''));

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'nama' wajib diisi.";

                continue;
            }

            if ($categoryName === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'kategori' wajib diisi.";

                continue;
            }

            if ($baseUnitCode === '') {
                $this->errors[] = "Baris {$rowNum}: kolom 'kode unit dasar' wajib diisi.";

                continue;
            }

            $category = MsItemCategory::where('name', $categoryName)->where('active', true)->first();
            if (! $category) {
                $this->errors[] = "Baris {$rowNum}: kategori item '{$categoryName}' tidak ditemukan atau tidak aktif.";

                continue;
            }

            $baseUnit = MsUnit::query()->where('code', $baseUnitCode)->where('active', true)->first();
            if ($baseUnit === null) {
                $this->errors[] = "Baris {$rowNum}: unit dasar dengan kode '{$baseUnitCode}' tidak ditemukan atau tidak aktif.";

                continue;
            }

            DB::transaction(function () use ($name, $description, $category, $baseUnit): void {
                $item = MsItem::create([
                    'code' => $this->codeService->next(AutoCodePrefix::MsItem),
                    'name' => $name,
                    'description' => $description ?: null,
                    'item_category_id' => $category->id,
                    'active' => true,
                ]);

                app(ManageItemUnitsService::class)->add($item, $baseUnit);
            });

            $this->successCount++;
        }
    }
}

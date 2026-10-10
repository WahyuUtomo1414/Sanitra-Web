<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Enums\UnitType;
use App\Models\MsItem;
use App\Models\MsItemCategory;
use App\Models\MsItemUnit;
use App\Models\MsUnit;

class ItemSeeder extends SanitraSeeder
{
    private const CATEGORIES = [
        'Bakteri Pengurai',
        'Bahan Kimia IPAL',
        'Peralatan Penunjang IPAL',
        'Jasa IPAL',
        'Jasa Perizinan Lingkungan',
        'Jasa Pest Control',
    ];

    private const UNITS = [
        ['JAM', 'Jam', UnitType::Waktu],
        ['HARI', 'Hari', UnitType::Waktu],
        ['BLN', 'Bulan', UnitType::Waktu],
        ['UNIT', 'Unit', UnitType::Barang],
        ['LTR', 'Liter', UnitType::Barang],
        ['KG', 'Kilogram', UnitType::Barang],
        ['PKT', 'Paket', UnitType::Barang],
    ];

    private const ITEMS = [
        ['Bakteri Pengurai IPAL', 'Bakteri Pengurai', 'KG'],
        ['Bahan Kimia Disinfeksi IPAL', 'Bahan Kimia IPAL', 'LTR'],
        ['Pompa Dosing IPAL', 'Peralatan Penunjang IPAL', 'UNIT'],
        ['Jasa Survei IPAL', 'Jasa IPAL', 'HARI'],
        ['Jasa Desain Teknis IPAL', 'Jasa IPAL', 'PKT'],
        ['Jasa Pemeliharaan IPAL', 'Jasa IPAL', 'BLN'],
        ['Jasa Pengurusan Izin Lingkungan', 'Jasa Perizinan Lingkungan', 'PKT'],
        ['Jasa Pest Control Gedung', 'Jasa Pest Control', 'PKT'],
    ];

    public function run(): void
    {
        $categories = $this->seedCategories();
        $units = $this->seedUnits();
        $this->seedItems($categories, $units);
    }

    /** @return array<string, MsItemCategory> */
    private function seedCategories(): array
    {
        $categories = [];

        foreach (self::CATEGORIES as $name) {
            $categories[$name] = $this->coded(MsItemCategory::class, AutoCodePrefix::MsItemCategory,
                ['name' => $name],
                ['description' => 'Kategori berdasarkan layanan dan produk yang ditampilkan Sanitra.', 'active' => true]);
        }

        return $categories;
    }

    /** @return array<string, MsUnit> */
    private function seedUnits(): array
    {
        $units = [];

        foreach (self::UNITS as [$code, $name, $type]) {
            $units[$code] = $this->uncoded(MsUnit::class,
                ['code' => $code],
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
        foreach (self::ITEMS as [$name, $categoryName, $unitCode]) {
            $item = $this->coded(MsItem::class, AutoCodePrefix::MsItem,
                ['name' => $name, 'item_category_id' => $categories[$categoryName]->id],
                ['description' => 'Contoh katalog berdasarkan layanan/produk Sanitra; bukan SKU atau harga resmi.', 'active' => true]);

            $this->uncoded(MsItemUnit::class,
                ['item_id' => $item->id, 'unit_id' => $units[$unitCode]->id],
                ['is_base' => true, 'conversion' => 1, 'active' => true]);
        }
    }
}

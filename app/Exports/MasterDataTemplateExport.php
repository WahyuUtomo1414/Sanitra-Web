<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MasterDataTemplateExport implements FromArray, WithHeadings, WithStyles
{
    private array $headings;

    private array $exampleRow;

    public function __construct(array $headings, array $exampleRow)
    {
        $this->headings = $headings;
        $this->exampleRow = $exampleRow;
    }

    public function array(): array
    {
        return [$this->exampleRow];
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @param  string  $entity  One of: company, employee, client, item_category, item, unit, bank_account
     */
    public static function forEntity(string $entity): self
    {
        return match ($entity) {
            'company' => new self(
                ['Nama', 'Nama Legal', 'Email', 'Telepon', 'Alamat', 'Kode Pos', 'Deskripsi'],
                ['PT Contoh Jaya', 'PT Contoh Jaya Tbk', 'contoh@email.com', '08123456789', 'Jl. Contoh No. 1, Jakarta', '12345', 'Deskripsi opsional'],
            ),
            'employee' => new self(
                ['Nama', 'Jabatan', 'Email', 'Telepon', 'Kode Perusahaan'],
                ['Budi Santoso', 'Marketing', 'budi@email.com', '08123456789', 'COM0001'],
            ),
            'client' => new self(
                ['Nama', 'Email', 'Telepon', 'Alamat', 'Deskripsi', 'Kode PIC'],
                ['Client Contoh', 'client@email.com', '08123456789', 'Jl. Contoh No. 1', 'Deskripsi opsional', 'EMP0001'],
            ),
            'item_category' => new self(
                ['Nama', 'Deskripsi'],
                ['Peralatan', 'Deskripsi opsional'],
            ),
            'item' => new self(
                ['Nama', 'Deskripsi', 'Kategori'],
                ['Laptop', 'Deskripsi opsional', 'Peralatan'],
            ),
            'unit' => new self(
                ['Kode', 'Nama', 'Tipe', 'Deskripsi'],
                ['JAM', 'Jam', 'waktu', 'Deskripsi opsional'],
            ),
            'bank_account' => new self(
                ['Nama', 'Nama Bank', 'Nomor Rekening', 'Nama Pemilik', 'Deskripsi'],
                ['Rekening Utama', 'BCA', '1234567890', 'PT Contoh', 'Deskripsi opsional'],
            ),
            default => throw new \InvalidArgumentException("Entity '{$entity}' tidak dikenali."),
        };
    }
}

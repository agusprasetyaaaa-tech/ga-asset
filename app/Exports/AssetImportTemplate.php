<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetImportTemplate implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'kode_aset', 'nama_aset', 'merek', 'model_tipe', 'serial_number',
            'kategori', 'subkategori', 'departemen', 'lokasi', 'status',
            'kondisi', 'pemegang', 'tanggal_pembelian', 'harga_pembelian',
            'vendor', 'tanggal_garansi', 'catatan',
        ];
    }

    public function array(): array
    {
        return [
            [
                'AST001/IT/1/2026', 'Laptop Dell Latitude', 'Dell', 'Latitude 5520',
                'SN-DELL-001', 'Elektronik', 'Laptop', 'IT Department', 'Gedung A Lt.1',
                'available', 'baik', 'John Doe', '2026-01-15', '15000000',
                'PT. Supplier ABC', '2027-01-15', 'Unit baru',
            ],
            [
                '', 'Monitor Samsung 27"', 'Samsung', 'LS27A', 'SN-SAM-001',
                'Elektronik', 'Monitor', 'Finance', 'Gedung B Lt.2', 'in_use',
                'cukup_baik', 'Jane Smith', '2025-06-20', '3500000',
                'PT. Supplier XYZ', '2026-06-20', 'Bekas pindah dari IT',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'],
                ],
            ],
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;
    protected $row = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Asset::with(['subcategory.category', 'location', 'department_rel', 'vendor_rel']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['category_id'])) {
            $query->whereHas('subcategory', fn($q) => $q->where('category_id', $this->filters['category_id']));
        }

        if (!empty($this->filters['subcategory_id'])) {
            $query->where('subcategory_id', $this->filters['subcategory_id']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'No', 'Kode Aset', 'Nama Aset', 'Merek', 'Model/Tipe', 'Serial Number',
            'Kategori', 'Subkategori', 'Departemen', 'Lokasi', 'Status', 'Kondisi',
            'Pemegang', 'Tanggal Pembelian', 'Harga Pembelian (Rp)', 'Nilai Residu (Rp)', 'Umur Ekonomis (Thn)', 'Nilai Buku (Rp)', 'Vendor/Supplier',
            'Tanggal Garansi', 'License Key', 'Tipe Lisensi', 'License Expired', 'Catatan',
        ];
    }

    public function map($asset): array
    {
        $this->row++;

        $statusMap = ['available' => 'Tersedia', 'in_use' => 'Digunakan', 'maintenance' => 'Perbaikan', 'damaged' => 'Rusak'];
        $conditionMap = ['baik' => 'Baik', 'cukup_baik' => 'Cukup Baik', 'kurang_baik' => 'Kurang Baik', 'rusak' => 'Rusak'];

        return [
            $this->row,
            $asset->asset_code,
            $asset->name,
            $asset->brand ?? '-',
            $asset->model_type ?? '-',
            $asset->serial_number,
            $asset->subcategory?->category?->name ?? '-',
            $asset->subcategory?->name ?? '-',
            $asset->department_rel?->name ?? $asset->department ?? '-',
            $asset->location?->name ?? '-',
            $statusMap[$asset->status] ?? $asset->status,
            $conditionMap[$asset->condition] ?? $asset->condition,
            $asset->current_holder ?? '-',
            $asset->purchase_date?->format('d/m/Y') ?? '-',
            $asset->purchase_price ? number_format($asset->purchase_price, 0, ',', '.') : '-',
            $asset->salvage_value ? number_format($asset->salvage_value, 0, ',', '.') : '-',
            $asset->useful_life ?? '-',
            $asset->current_book_value ? number_format($asset->current_book_value, 0, ',', '.') : '-',
            $asset->vendor_rel?->name ?? $asset->vendor ?? '-',
            $asset->warranty_date?->format('d/m/Y') ?? '-',
            $asset->license_key ?? '-',
            $asset->license_type ?? '-',
            $asset->license_expiration_date?->format('d/m/Y') ?? '-',
            $asset->notes ?? '-',
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
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}

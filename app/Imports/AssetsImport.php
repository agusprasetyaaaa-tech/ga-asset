<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\Subcategory;
use App\Models\Department;
use App\Models\Location;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AssetsImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $rowCount = 0;
    protected $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Lookup subcategory by name (and optionally category)
                $subcategoryId = null;
                if (!empty($row['subkategori'])) {
                    $subQuery = Subcategory::where('name', 'like', '%' . trim($row['subkategori']) . '%');
                    if (!empty($row['kategori'])) {
                        $subQuery->whereHas('category', fn($q) => $q->where('name', 'like', '%' . trim($row['kategori']) . '%'));
                    }
                    $sub = $subQuery->first();
                    $subcategoryId = $sub?->id;
                }

                // Lookup department
                $departmentId = null;
                $departmentName = null;
                if (!empty($row['departemen'])) {
                    $dept = Department::where('name', 'like', '%' . trim($row['departemen']) . '%')->first();
                    $departmentId = $dept?->id;
                    $departmentName = $dept?->name ?? trim($row['departemen']);
                }

                // Lookup location
                $locationId = null;
                if (!empty($row['lokasi'])) {
                    $loc = Location::where('name', 'like', '%' . trim($row['lokasi']) . '%')->first();
                    $locationId = $loc?->id;
                }

                // Lookup or create vendor
                $vendorId = null;
                $vendorName = null;
                if (!empty($row['vendor'])) {
                    $vendor = Vendor::firstOrCreate(
                        ['name' => trim($row['vendor'])],
                        ['name' => trim($row['vendor'])]
                    );
                    $vendorId = $vendor->id;
                    $vendorName = $vendor->name;
                }

                // Parse dates
                $purchaseDate = $this->parseDate($row['tanggal_pembelian'] ?? null);
                $warrantyDate = $this->parseDate($row['tanggal_garansi'] ?? null);

                // Generate asset code if not provided
                $assetCode = !empty($row['kode_aset'])
                    ? trim($row['kode_aset'])
                    : Asset::generateAssetCode($subcategoryId, $departmentId, $purchaseDate?->toDateString());

                // Check for duplicate asset_code
                if (Asset::where('asset_code', $assetCode)->exists()) {
                    $this->errors[] = "Baris " . ($index + 2) . ": Kode aset '{$assetCode}' sudah ada.";
                    continue;
                }

                // Check for duplicate serial_number
                $serialNumber = trim($row['serial_number']);
                if (Asset::where('serial_number', $serialNumber)->exists()) {
                    $this->errors[] = "Baris " . ($index + 2) . ": Serial number '{$serialNumber}' sudah ada.";
                    continue;
                }

                // Validate status and condition
                $status = in_array($row['status'] ?? '', ['available', 'in_use', 'maintenance', 'damaged'])
                    ? $row['status'] : 'available';
                $condition = in_array($row['kondisi'] ?? '', ['baik', 'cukup_baik', 'kurang_baik', 'rusak'])
                    ? $row['kondisi'] : 'baik';

                Asset::create([
                    'asset_code' => $assetCode,
                    'barcode_code' => $assetCode,
                    'name' => trim($row['nama_aset']),
                    'brand' => $row['merek'] ?? null,
                    'model_type' => $row['model_tipe'] ?? null,
                    'serial_number' => $serialNumber,
                    'subcategory_id' => $subcategoryId,
                    'department_id' => $departmentId,
                    'department' => $departmentName,
                    'location_id' => $locationId,
                    'vendor_id' => $vendorId,
                    'vendor' => $vendorName,
                    'status' => $status,
                    'condition' => $condition,
                    'current_holder' => $row['pemegang'] ?? null,
                    'purchase_date' => $purchaseDate,
                    'purchase_price' => is_numeric($row['harga_pembelian'] ?? null) ? $row['harga_pembelian'] : null,
                    'warranty_date' => $warrantyDate,
                    'notes' => $row['catatan'] ?? null,
                ]);

                $this->rowCount++;
            } catch (\Exception $e) {
                $this->errors[] = "Baris " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    public function rules(): array
    {
        return [
            'nama_aset' => 'required|string',
            'serial_number' => 'required|string',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_aset.required' => 'Nama aset wajib diisi.',
            'serial_number.required' => 'Serial number wajib diisi.',
        ];
    }

    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;
        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

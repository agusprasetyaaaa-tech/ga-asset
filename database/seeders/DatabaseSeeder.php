<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Location;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\MaintenanceLog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with sample inventory data.
     */
    public function run(): void
    {
        // 1. Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'it_support@interprima.co.id',
            'password' => bcrypt('28Mei1998Tio'),
            'email_verified_at' => now(),
        ]);

        // 2. Create sample locations
        $locations = [
            'Gudang Utama' => Location::create(['name' => 'Gudang Utama', 'description' => 'Gudang penyimpanan utama']),
            'Kantor IT' => Location::create(['name' => 'Kantor IT', 'description' => 'Ruang divisi IT']),
            'Ruang Meeting' => Location::create(['name' => 'Ruang Meeting', 'description' => 'Ruang meeting utama']),
            'Server Room' => Location::create(['name' => 'Ruang Server', 'description' => 'Data Center']),
        ];

        // 3. Create Categories & Subcategories
        $catIT = Category::create(['name' => 'Electronic & IT', 'description' => 'Peralatan elektronik dan IT']);
        $catOffice = Category::create(['name' => 'Office Supply', 'description' => 'Peralatan kantor']);

        $subLaptop = Subcategory::create(['category_id' => $catIT->id, 'name' => 'Laptop', 'description' => 'Laptop & Notebook']);
        $subMonitor = Subcategory::create(['category_id' => $catIT->id, 'name' => 'Monitor', 'description' => 'Layar Monitor']);
        $subPrinter = Subcategory::create(['category_id' => $catOffice->id, 'name' => 'Printer', 'description' => 'Alat Cetak']);
        $subServer = Subcategory::create(['category_id' => $catIT->id, 'name' => 'Server & Network', 'description' => 'Infrastruktur IT']);

        // 4. Create sample assets with extended fields
        $assetsData = [
            [
                'name' => 'Laptop Dell Latitude 5540',
                'brand' => 'Dell',
                'model_type' => 'Latitude 5540',
                'subcategory_id' => $subLaptop->id,
                'serial_number' => 'DLL-2024-001',
                'specification' => 'Intel Core i7-1365U, 16GB DDR5 RAM, 512GB NVMe SSD, 15.6" FHD IPS',
                'condition' => 'baik',
                'status' => 'in_use',
                'current_holder' => 'Budi Santoso',
                'department' => 'IT',
                'location_id' => $locations['Kantor IT']->id,
                'description' => 'Laptop untuk divisi IT',
                'purchase_date' => '2024-01-15',
                'purchase_price' => 15000000,
                'vendor' => 'PT. Datascrip',
                'warranty_date' => '2027-01-15',
                'usage_period' => '5 Tahun',
            ],
            [
                'name' => 'Monitor LG 27" 4K',
                'brand' => 'LG',
                'model_type' => '27UL850-W',
                'subcategory_id' => $subMonitor->id,
                'serial_number' => 'LG-2024-002',
                'specification' => '27 inch 4K UHD (3840x2160), IPS Panel, USB-C, HDR400',
                'condition' => 'baik',
                'status' => 'available',
                'department' => 'IT',
                'location_id' => $locations['Gudang Utama']->id,
                'description' => 'Monitor untuk workstation',
                'purchase_date' => '2024-02-10',
                'purchase_price' => 5500000,
                'vendor' => 'PT. Harrisma',
                'warranty_date' => '2026-02-10',
                'usage_period' => '3 Tahun',
            ],
            [
                'name' => 'Printer HP LaserJet Pro',
                'brand' => 'HP',
                'model_type' => 'LaserJet Pro M479fdw',
                'subcategory_id' => $subPrinter->id,
                'serial_number' => 'HP-2024-003',
                'specification' => 'Color LaserJet, 28ppm, Duplex, WiFi, ADF',
                'condition' => 'kurang_baik',
                'status' => 'maintenance',
                'current_holder' => 'Andi Wijaya',
                'department' => 'General Affairs',
                'location_id' => $locations['Ruang Meeting']->id,
                'description' => 'Printer warna untuk ruang meeting',
                'purchase_date' => '2023-08-20',
                'purchase_price' => 8000000,
                'vendor' => 'PT. Synnex Metrodata',
                'warranty_date' => '2025-08-20',
                'usage_period' => '3 Tahun',
            ],
            [
                'name' => 'Server Rack Dell PowerEdge',
                'brand' => 'Dell',
                'model_type' => 'PowerEdge R750xs',
                'subcategory_id' => $subServer->id,
                'serial_number' => 'DLL-2023-005',
                'specification' => 'Intel Xeon Silver 4314, 64GB ECC RAM, 2x 1.2TB SAS, RAID H755',
                'condition' => 'baik',
                'status' => 'in_use',
                'current_holder' => 'Tim Server',
                'department' => 'IT',
                'location_id' => $locations['Server Room']->id,
                'description' => 'Server utama production',
                'purchase_date' => '2023-06-01',
                'purchase_price' => 85000000,
                'vendor' => 'PT. Multipolar Technology',
                'warranty_date' => '2028-06-01',
                'usage_period' => '7 Tahun',
            ],
        ];

        $assets = [];
        foreach ($assetsData as $data) {
            $assetCode = Asset::generateAssetCode();
            $assets[] = Asset::create([
                'asset_code' => $assetCode,
                'barcode_code' => $assetCode,
                'name' => $data['name'],
                'brand' => $data['brand'],
                'model_type' => $data['model_type'],
                'subcategory_id' => $data['subcategory_id'],
                'serial_number' => $data['serial_number'],
                'specification' => $data['specification'],
                'condition' => $data['condition'],
                'status' => $data['status'],
                'current_holder' => $data['current_holder'] ?? null,
                'department' => $data['department'] ?? null,
                'location_id' => $data['location_id'],
                'description' => $data['description'],
                'purchase_date' => $data['purchase_date'],
                'purchase_price' => $data['purchase_price'],
                'vendor' => $data['vendor'],
                'warranty_date' => $data['warranty_date'],
                'usage_period' => $data['usage_period'],
            ]);
        }

        // 5. Create sample movements
        if (isset($assets[0], $locations['Gudang Utama'])) {
            AssetMovement::create([
                'asset_id' => $assets[0]->id,
                'from_location_id' => $locations['Gudang Utama']->id,
                'to_location_id' => $locations['Kantor IT']->id,
                'user_id' => $admin->id,
                'from_holder' => null,
                'to_holder' => 'Budi Santoso',
                'notes' => 'Distribusi laptop baru ke divisi IT.',
            ]);
        }

        // 6. Create sample maintenance logs
        if (isset($assets[2])) {
            MaintenanceLog::create([
                'asset_id' => $assets[2]->id,
                'user_id' => $admin->id,
                'type' => 'repair',
                'description' => 'Toner habis, perlu penggantian cartridge warna.',
                'cost' => 350000,
                'technician' => 'Teknisi HP Authorized',
            ]);
        }
    }
}

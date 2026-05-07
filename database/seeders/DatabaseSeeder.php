<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with essential data only.
     */
    public function run(): void
    {
        // Jalankan Inisialisasi Role, Permission, dan Akun Utama
        $this->call(RolePermissionSeeder::class);

        // Data dummy aset, lokasi, dan kategori telah dihapus 
        // agar database bersih saat dimulai dari nol.
    }
}

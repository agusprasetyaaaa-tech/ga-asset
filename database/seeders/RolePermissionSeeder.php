<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat Role
        $adminRole = Role::create(['name' => 'admin']);
        $staffRole = Role::create(['name' => 'staff']);

        // 2. Buat Permission Lengkap
        $permissions = [
            'view dashboard',
            'view assets',
            'create assets',
            'edit assets',
            'delete assets',
            
            // Database Permissions (Categories, Locations, etc)
            'view database',
            'create database',
            'edit database',
            'delete database',

            // Activity Permissions (Loans, Maintenance, Audit)
            'view activity',
            'create activity',
            'edit activity',
            'delete activity',

            // User Management
            'manage users',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 3. Admin: Berikan SEMUA permission
        $adminRole->givePermissionTo(Permission::all());

        // 4. Staff: HANYA View dan Create (Input)
        // Tidak diberikan permission 'edit' atau 'delete'
        $staffRole->givePermissionTo([
            'view dashboard',
            'view assets', 
            'create assets', 
            'view database', 
            'create database',
            'view activity',
            'create activity'
        ]);

        // 5. Buat User Admin
        $admin = User::updateOrCreate(
            ['email' => 'it_support@interprima.co.id'],
            [
                'name' => 'Administrator IT',
                'password' => Hash::make('@28Mei1998Tio'),
            ]
        );
        $admin->assignRole($adminRole);

        // 6. Buat User Staff
        $staff = User::updateOrCreate(
            ['email' => 'arie@interprima.co.id'],
            [
                'name' => 'ARIE',
                'password' => Hash::make('@Arie2K26'),
            ]
        );
        $staff->assignRole($staffRole);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Branch;
use App\Models\Category;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─────────────────────────────────────────────
        // DEFINISI SEMUA PERMISSION
        // ─────────────────────────────────────────────
        $permissions = [
            // Dashboard
            'view dashboard global',
            'view dashboard branch',
            'view dashboard sales',

            // Branch (Cabang)
            'view branches',
            'create branches',
            'edit branches',
            'delete branches',

            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',

            // Category
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',

            // Product (Produk Jas)
            'view products',
            'create products',
            'edit products',
            'delete products',
            'generate product qr',

            // Customer
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            'blacklist customers',

            // Rental (Penyewaan)
            'view rentals',
            'view all rentals',          // Super Admin - semua cabang
            'view branch rentals',       // Admin - cabang sendiri
            'create rentals',
            'edit rentals',
            'cancel rentals',
            'process payment',
            'process return',
            'print invoice',
            'send whatsapp',

            // Report
            'view reports',
            'view all reports',
            'export pdf',
            'export excel',

            // System
            'view activity logs',
            'view all activity logs',
            'backup database',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ─────────────────────────────────────────────
        // 1. SUPER ADMIN — Akses semua fitur
        // ─────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // ─────────────────────────────────────────────
        // 2. ADMIN TOKO — Kelola cabang sendiri
        // ─────────────────────────────────────────────
        $adminToko = Role::firstOrCreate(['name' => 'admin_toko', 'guard_name' => 'web']);
        $adminToko->syncPermissions([
            'view dashboard branch',
            'view products',
            'create products',
            'edit products',
            'generate product qr',
            'view customers',
            'create customers',
            'edit customers',
            'blacklist customers',
            'view branch rentals',
            'create rentals',
            'edit rentals',
            'cancel rentals',
            'process payment',
            'process return',
            'print invoice',
            'send whatsapp',
            'view reports',
            'export pdf',
            'export excel',
            'view activity logs',
        ]);

        // ─────────────────────────────────────────────
        // 3. SALES — Transaksi harian saja
        // ─────────────────────────────────────────────
        $sales = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $sales->syncPermissions([
            'view dashboard sales',
            'view products',
            'view customers',
            'create customers',
            'view branch rentals',
            'create rentals',
            'process payment',
            'process return',
            'print invoice',
            'send whatsapp',
        ]);

        $this->command->info('✅ Roles & Permissions berhasil dibuat:');
        $this->command->info('   - super_admin: ' . $superAdmin->permissions->count() . ' permissions');
        $this->command->info('   - admin_toko:  ' . $adminToko->permissions->count() . ' permissions');
        $this->command->info('   - sales:       ' . $sales->permissions->count() . ' permissions');
    }
}

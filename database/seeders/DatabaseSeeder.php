<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Customer;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────
        // CABANG
        // ─────────────────────────────────────
        $branchPusat = Branch::create([
            'name'     => 'Toko Pusat - Yogyakarta',
            'code'     => 'YGY01',
            'address'  => 'Jl. Malioboro No. 88, Yogyakarta',
            'phone'    => '0274-123456',
            'email'    => 'pusat@jasrental.id',
            'city'     => 'Yogyakarta',
            'province' => 'DI Yogyakarta',
            'is_active'=> true,
        ]);

        $branchSolo = Branch::create([
            'name'     => 'Cabang Solo',
            'code'     => 'SLO01',
            'address'  => 'Jl. Slamet Riyadi No. 45, Solo',
            'phone'    => '0271-654321',
            'email'    => 'solo@jasrental.id',
            'city'     => 'Surakarta',
            'province' => 'Jawa Tengah',
            'is_active'=> true,
        ]);

        // ─────────────────────────────────────
        // USERS (ganti assignRole() → kolom role)
        // ─────────────────────────────────────

        User::create([
            'name'      => 'Super Administrator',
            'email'     => 'superadmin@jasrental.id',
            'password'  => Hash::make('password'),
            'phone'     => '081234567890',
            'role'      => 'super_admin',   // ← langsung isi kolom
            'branch_id' => null,
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'Admin Pusat Yogya',
            'email'     => 'admin.pusat@jasrental.id',
            'password'  => Hash::make('password'),
            'phone'     => '081298765432',
            'role'      => 'admin_toko',
            'branch_id' => $branchPusat->id,
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'Admin Cabang Solo',
            'email'     => 'admin.solo@jasrental.id',
            'password'  => Hash::make('password'),
            'phone'     => '082187654321',
            'role'      => 'admin_toko',
            'branch_id' => $branchSolo->id,
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'Sales Pusat - Budi',
            'email'     => 'sales.budi@jasrental.id',
            'password'  => Hash::make('password'),
            'phone'     => '085312345678',
            'role'      => 'sales',
            'branch_id' => $branchPusat->id,
            'is_active' => true,
        ]);

        User::create([
            'name'      => 'Sales Solo - Andi',
            'email'     => 'sales.andi@jasrental.id',
            'password'  => Hash::make('password'),
            'phone'     => '085398765432',
            'role'      => 'sales',
            'branch_id' => $branchSolo->id,
            'is_active' => true,
        ]);

        // ─────────────────────────────────────
        // KATEGORI
        // ─────────────────────────────────────
        $categories = [
            ['name' => 'Jas Formal',    'slug' => 'jas-formal',    'icon' => 'briefcase',      'sort_order' => 1],
            ['name' => 'Tuxedo',        'slug' => 'tuxedo',        'icon' => 'award',          'sort_order' => 2],
            ['name' => 'Jas Wisuda',    'slug' => 'jas-wisuda',    'icon' => 'graduation-cap', 'sort_order' => 3],
            ['name' => 'Kebaya',        'slug' => 'kebaya',        'icon' => 'sparkles',       'sort_order' => 4],
            ['name' => 'Rompi',         'slug' => 'rompi',         'icon' => 'layers',         'sort_order' => 5],
            ['name' => 'Sepatu',        'slug' => 'sepatu',        'icon' => 'footprints',     'sort_order' => 6],
            ['name' => 'Aksesoris',     'slug' => 'aksesoris',     'icon' => 'watch',          'sort_order' => 7],
        ];

        foreach ($categories as $cat) {
            Category::create(array_merge($cat, ['is_active' => true]));
        }

        // ─────────────────────────────────────
        // PRODUK — Cabang Pusat
        // ─────────────────────────────────────
        $catJasFormal = Category::where('slug', 'jas-formal')->first();
        $catTuxedo    = Category::where('slug', 'tuxedo')->first();
        $catWisuda    = Category::where('slug', 'jas-wisuda')->first();

        $products = [
            ['name' => 'Jas Formal Hitam Classic', 'category_id' => $catJasFormal->id, 'size' => 'L',  'color' => 'Hitam', 'rental_price' => 75000,  'stock_total' => 3],
            ['name' => 'Jas Formal Navy Premium',  'category_id' => $catJasFormal->id, 'size' => 'M',  'color' => 'Navy',  'rental_price' => 85000,  'stock_total' => 2],
            ['name' => 'Tuxedo Putih Elegant',     'category_id' => $catTuxedo->id,    'size' => 'L',  'color' => 'Putih', 'rental_price' => 125000, 'stock_total' => 2],
            ['name' => 'Tuxedo Hitam Modern',      'category_id' => $catTuxedo->id,    'size' => 'XL', 'color' => 'Hitam', 'rental_price' => 115000, 'stock_total' => 2],
            ['name' => 'Jas Wisuda Biru Dongker',  'category_id' => $catWisuda->id,    'size' => 'M',  'color' => 'Biru',  'rental_price' => 65000,  'stock_total' => 4],
            ['name' => 'Jas Wisuda Abu-Abu',       'category_id' => $catWisuda->id,    'size' => 'S',  'color' => 'Abu',   'rental_price' => 65000,  'stock_total' => 3],
        ];

        foreach ($products as $i => $p) {
            Product::create(array_merge($p, [
                'branch_id'       => $branchPusat->id,
                'code'            => 'PRD0100' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                'stock_available' => $p['stock_total'],
                'condition'       => 'excellent',
                'status'          => 'available',
                'deposit_price'   => 0,
            ]));
        }

        // ─────────────────────────────────────
        // CUSTOMER
        // ─────────────────────────────────────
        $customers = [
            ['name' => 'Ahmad Fauzi',  'phone' => '081234000001', 'suit_size' => 'L',  'shirt_size' => 'M'],
            ['name' => 'Budi Santoso', 'phone' => '081234000002', 'suit_size' => 'M',  'shirt_size' => 'S'],
            ['name' => 'Citra Dewi',   'phone' => '081234000003', 'suit_size' => 'S',  'shirt_size' => 'XS'],
            ['name' => 'Dian Purnama', 'phone' => '081234000004', 'suit_size' => 'XL', 'shirt_size' => 'L'],
            ['name' => 'Eko Prasetyo', 'phone' => '081234000005', 'suit_size' => 'L',  'shirt_size' => 'M'],
            ['name' => 'Fajar Nugroho', 'phone' => '081234000006', 'suit_size' => 'XXL', 'shirt_size' => 'XL'],
        ];

        foreach ($customers as $c) {
            Customer::create(array_merge($c, [
                'branch_id'      => $branchPusat->id,
                'is_blacklisted' => false,
            ]));
        }

        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════╗');
        $this->command->info('║      JasRental — Data Awal Berhasil     ║');
        $this->command->info('╠══════════════════════════════════════════╣');
        $this->command->info('║  AKUN LOGIN:                             ║');
        $this->command->info('║  superadmin@jasrental.id  → super_admin  ║');
        $this->command->info('║  admin.pusat@jasrental.id → admin_toko   ║');
        $this->command->info('║  admin.solo@jasrental.id  → admin_toko   ║');
        $this->command->info('║  sales.budi@jasrental.id  → sales        ║');
        $this->command->info('║  sales.andi@jasrental.id  → sales        ║');
        $this->command->info('║  Password semua: password                ║');
        $this->command->info('╚══════════════════════════════════════════╝');
    }
}
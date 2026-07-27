## Daftar Isi

- [Tentang Project](#tentang-project)
- [Fitur Utama](#fitur-utama)
- [Alur Bisnis: Verifikasi Jaminan](#alur-bisnis-verifikasi-jaminan)
- [Tech Stack](#tech-stack)
- [Struktur Folder](#struktur-folder)
- [Instalasi](#instalasi)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Struktur Database (Ringkas)](#struktur-database-ringkas)
- [Role & Hak Akses](#role--hak-akses)
- [Catatan Pengembangan](#catatan-pengembangan)
- [Kontribusi](#kontribusi)

---

## Tentang Project

Sistem ini digunakan untuk mengelola operasional bisnis penyewaan (mis. penyewaan jas/pakaian) di beberapa cabang (branch), dengan fitur:

- Manajemen customer dan histori transaksi
- Verifikasi jaminan (KTP, SIM, Deposit uang, atau jaminan custom) sebelum transaksi disetujui
- Perhitungan denda keterlambatan otomatis berdasarkan paket sewa
- Invoice digital, cetak thermal, dan PDF
- Notifikasi WhatsApp otomatis ke customer (invoice & reminder)
- QR Code untuk verifikasi/scan status penyewaan
- Multi-cabang dengan pembatasan akses berdasarkan `branch_id`

## Fitur Utama

| Modul | Deskripsi |
|---|---|
| **Penyewaan Baru** | Form multi-step: pilih customer → pilih barang → data jaminan & foto → konfirmasi |
| **Verifikasi Jaminan** | KTP/SIM **wajib** difoto untuk customer baru atau customer lama yang belum punya foto tersimpan; Deposit/Custom bersifat opsional |
| **Pembayaran** | Pencatatan pembayaran per transaksi (`processPayment`) |
| **Diskon** | Update diskon transaksi (`updateDiscount`) |
| **Pembatalan** | Pembatalan transaksi dengan alasan (`cancel`) |
| **Invoice & Cetak** | Invoice web, invoice publik (via token), cetak thermal, export PDF |
| **QR Code** | Generate & download QR code per transaksi, scan untuk cek status |
| **WhatsApp** | Kirim invoice dan reminder pembayaran langsung via WhatsApp Web link |
| **Laporan** | Modul `reports` (outstanding, revenue, returns) — lihat folder `resources/views/reports` |

## Alur Bisnis: Verifikasi Jaminan

Bagian ini adalah salah satu logika inti aplikasi, jadi didokumentasikan secara khusus:

1. Saat memilih jenis jaminan **KTP** atau **SIM**, foto jaminan bersifat **wajib**.
2. Jika customer **sudah pernah bertransaksi sebelumnya dan punya foto tersimpan** di database (`customer.id_photo`), foto lama otomatis dipakai ulang — tidak perlu upload lagi, kecuali fotonya sudah tidak jelas/kadaluarsa.
3. Jika customer **belum punya foto tersimpan**, sistem membedakan dua pesan:
   - **Customer baru** (`rental_count == 0`): belum pernah menyewa sama sekali → foto wajib diunggah untuk transaksi pertama.
   - **Customer lama tanpa foto** (`rental_count > 0`): sudah pernah bertransaksi tapi datanya belum lengkap → foto tetap wajib diunggah sebelum transaksi bisa disimpan.
4. Untuk jaminan **Deposit** atau **Custom**, foto tidak wajib (opsional) — namun tetap disarankan sebagai bukti serah-terima.
5. Validasi diterapkan di **dua lapis**:
   - **Frontend (Alpine.js)** — tombol lanjut ke step berikutnya dinonaktifkan (`hasValidIdVerification`) selama syarat foto belum terpenuhi.
   - **Backend (`RentalController@store`)** — validasi ulang di server sebelum data disimpan, supaya tidak bisa dilewati dengan memanipulasi request langsung.
6. Foto & nomor identitas disimpan langsung ke record `Customer` (bukan hanya ke transaksi), supaya bisa dipakai ulang di transaksi berikutnya. Foto lama dihapus dari storage **setelah** transaksi baru berhasil disimpan (di luar DB transaction, karena penghapusan file tidak bisa di-rollback).

## Tech Stack

- **Backend**: PHP / Laravel `[isi versi, mis. 10.x]`
- **Frontend**: Blade + Alpine.js + Tailwind CSS
- **QR Code**: `simplesoftwareio/simple-qrcode`
- **PDF**: `barryvdh/laravel-dompdf`
- **Database**: `[isi, mis. MySQL 8]`
- **Auth**: `[isi, mis. Laravel Breeze/Sanctum/Fortify]`

## Struktur Folder

```
resources/
└── views/
    ├── products/
    ├── profile/
    ├── rentals/
    │   ├── create.blade.php          # Form buat penyewaan baru (multi-step)
    │   ├── index.blade.php           # Daftar semua penyewaan
    │   ├── show.blade.php            # Detail penyewaan
    │   ├── edit.blade.php
    │   ├── invoice.blade.php         # Invoice (web view)
    │   ├── invoice-public.blade.php  # Invoice via public token (tanpa login)
    │   ├── pdf.blade.php             # Template untuk export PDF
    │   ├── thermal.blade.php         # Template struk thermal
    │   ├── scan.blade.php            # Halaman scan QR
    │   └── scan-result.blade.php     # Hasil scan QR
    └── reports/
        ├── partials/
        ├── pdf/
        ├── outstanding.blade.php
        ├── returns.blade.php
        └── revenue.blade.php

app/
├── Http/Controllers/
│   └── RentalController.php
├── Models/
│   ├── Rental.php
│   ├── Customer.php
│   ├── Product.php
│   ├── Category.php
│   └── RentalPackage.php
└── Services/
    ├── RentalService.php             # Logika perhitungan & pembuatan rental
    ├── NotificationService.php       # Notifikasi internal (admin/superadmin)
    └── WhatsAppMessageService.php    # Bangun pesan & link WhatsApp
```

## Instalasi

```bash
# 1. Clone repository
git clone https://github.com/username/nama-repo.git
cd nama-repo

# 2. Install dependency PHP
composer install

# 3. Install dependency frontend
npm install

# 4. Salin file environment
cp .env.example .env
php artisan key:generate

# 5. Jalankan migrasi & seeder (kalau ada)
php artisan migrate --seed

# 6. Buat symbolic link storage (untuk foto customer/jaminan)
php artisan storage:link
```

## Konfigurasi Environment

Sesuaikan `.env` dengan minimal variabel berikut:

```env
APP_NAME="[Nama Aplikasi]"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=[nama_database]
DB_USERNAME=[user]
DB_PASSWORD=[password]

FILESYSTEM_DISK=public
```

> Foto jaminan (KTP/SIM) disimpan di `storage/app/public/customers/id-photos`. Pastikan `php artisan storage:link` sudah dijalankan agar file bisa diakses via `asset('storage/...')`.

## Menjalankan Aplikasi

```bash
# Jalankan server lokal
php artisan serve

# Compile asset (development, dengan hot reload)
npm run dev

# Compile asset (production)
npm run build
```

Buka `http://localhost:8000` di browser.

## Struktur Database (Ringkas)

Berdasarkan relasi yang terlihat di kode:

| Tabel | Kolom penting | Keterangan |
|---|---|---|
| `customers` | `id_number`, `id_photo`, `notes`, `is_blacklisted`, `branch_id` | Data customer + foto jaminan tersimpan |
| `rentals` | `invoice_number`, `rental_status`, `payment_status`, `rental_date`, `branch_id`, `public_token`, `qr_code` | Transaksi penyewaan |
| `rental_packages` | `duration_days`, `penalty_percent`, `is_custom` | Paket durasi sewa & denda |
| `products` | `stock_available`, `status`, `branch_id` | Barang yang disewakan |
| `guarantees` | terkait `rentals` (relasi `guarantees`) | Data jaminan per transaksi |

> `[Lengkapi dengan skema migration lengkap dari folder `database/migrations` project Anda]`

## Role & Hak Akses

- **Super Admin**: akses semua cabang, bisa menghapus transaksi (`destroy`)
- **Admin Toko**: akses terbatas ke `branch_id` miliknya, menerima notifikasi transaksi baru
- `[Lengkapi role lain sesuai `RentalPolicy` dan middleware yang dipakai]`

## Catatan Pengembangan

- Logika `buildWhatsAppMessage()` dan `buildReminderMessage()` sudah dipindahkan ke `App\Services\WhatsAppMessageService` untuk menghindari duplikasi kode (sebelumnya ada di dua method controller terpisah).
- Method `update()` pada `RentalController` saat ini hanya meng-update field dasar (`customer_id`, `rental_date`, `discount`, `notes`). Jika perlu update `items` atau `guarantee`, sebaiknya didelegasikan ke `RentalService` (seperti `createRental()`) supaya perhitungan `total_amount`/`subtotal` tetap konsisten di satu tempat.
- Kolom `cancel_reason`, `cancelled_at`, `cancelled_by` pada method `cancel()` mengasumsikan sudah ada di migration tabel `rentals` — periksa kembali sebelum deploy.

## Kontribusi

1. Buat branch baru dari `main`: `git checkout -b fitur/nama-fitur`
2. Commit dengan pesan yang jelas (disarankan format [Conventional Commits](https://www.conventionalcommits.org/)):
   ```
   feat: tambah validasi foto jaminan wajib untuk KTP/SIM
   fix: perbaiki perhitungan denda keterlambatan
   ```
3. Push dan buat Pull Request ke `main` untuk direview sebelum merge.

---


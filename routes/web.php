<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LaundryController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
// FIX: import AuditLogController dengan namespace Admin yang benar
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\SupportMessageController;

// ── Invoice publik (tanpa auth) — pakai token acak, BUKAN invoice_number ──────
Route::get('/invoice/{token}', [RentalController::class, 'invoicePublic'])
    ->name('rentals.invoice.public');

Route::get('/invoice/{token}/pdf', [RentalController::class, 'invoicePdfPublic'])
    ->name('rentals.invoice.pdf.public');

// ── AUTH ──────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login',  [AuthenticatedSessionController::class, 'create'])->name('login');
    // FIX KEAMANAN: batasi percobaan login (5x per menit per IP+email) untuk
    // mencegah brute-force. Alias 'throttle' sudah tersedia bawaan Laravel.
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.post');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ── ROOT ──────────────────────────────────────────────────────────────────────
Route::get('/', fn() => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login')
);

// ═════════════════════════════════════════════════════════════════════════════
// AUTHENTICATED ROUTES
// ═════════════════════════════════════════════════════════════════════════════
Route::middleware(['auth', \App\Http\Middleware\EnsureBranchScope::class])->group(function () {

    // ── DASHBOARD ─────────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── PROFILE ───────────────────────────────────────────────────────────────
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/',          [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/',        [ProfileController::class, 'update'])->name('update');
        Route::delete('/',       [ProfileController::class, 'destroy'])->name('destroy');
        Route::put('/password',  [ProfileController::class, 'updatePassword'])->name('password');
        Route::post('/theme',    [ProfileController::class, 'updateTheme'])->name('theme');
    });

    // ─── PESAN KE ADMIN (Kontak Admin / Laporan Bug) ─────────────────────────
    Route::prefix('support-messages')->name('support-messages.')->group(function () {
        Route::post('/', [SupportMessageController::class, 'store'])->name('store');

        Route::middleware('role:super_admin')->group(function () {
            Route::get('/',               [SupportMessageController::class, 'index'])->name('index');
            Route::patch('/{supportMessage}/read', [SupportMessageController::class, 'markRead'])->name('read');
            Route::get('/unread-count',   [SupportMessageController::class, 'unreadCount'])->name('unread-count');
        });
    });

    // ── NOTIFIKASI ────────────────────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/',           [NotificationController::class, 'index'])->name('index');
        Route::get('/data',       [NotificationController::class, 'data'])->name('data');
        Route::get('/count',      [NotificationController::class, 'count'])->name('count');
        Route::get('/{id}',       [NotificationController::class, 'show'])->name('show');
        Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all',  [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::delete('/{id}',    [NotificationController::class, 'destroy'])->name('destroy');
    });

    // ═════════════════════════════════════════════════════════════════════════
    // TRANSAKSI
    // ═════════════════════════════════════════════════════════════════════════
    Route::prefix('rentals')->name('rentals.')->group(function () {
        Route::get('/scan',          [RentalController::class, 'scanPage'])->name('scan');
        Route::get('/scan/{invoice}',[RentalController::class, 'scanQr'])->name('scan.show');
        Route::get('/',              [RentalController::class, 'index'])->name('index');
        Route::get('/create',        [RentalController::class, 'create'])->name('create');
        Route::post('/',             [RentalController::class, 'store'])->name('store');
        Route::get('/{rental}',      [RentalController::class, 'show'])->name('show');
        Route::get('/{rental}/qr-download', [RentalController::class, 'downloadQr'])->name('qr.download');
        Route::post('/{rental}/payment',    [RentalController::class, 'processPayment'])->name('payment');
        Route::post('/{rental}/return',     [RentalController::class, 'processReturn'])->name('return');
        Route::get('/{rental}/invoice',     [RentalController::class, 'invoice'])->name('invoice');
        Route::get('/{rental}/thermal',     [RentalController::class, 'thermalPrint'])->name('thermal');
        Route::get('/{rental}/pdf',         [RentalController::class, 'exportPdf'])->name('pdf');
        Route::get('/{rental}/whatsapp',    [RentalController::class, 'whatsapp'])->name('whatsapp');
        Route::get('/{rental}/reminder',    [RentalController::class, 'sendReminder'])->name('reminder');

        Route::middleware('role:super_admin,admin_toko')->group(function () {
            Route::patch('/{rental}/cancel', [RentalController::class, 'cancel'])->name('cancel');
            Route::get('/{rental}/edit',     [RentalController::class, 'edit'])->name('edit');
            Route::patch('/{rental}',        [RentalController::class, 'update'])->name('update');
            Route::patch('/{rental}/discount', [RentalController::class, 'updateDiscount'])->name('discount.update');
        });

        Route::middleware('role:super_admin')
            ->delete('/{rental}', [RentalController::class, 'destroy'])->name('destroy');
    });

    // ═════════════════════════════════════════════════════════════════════════
    // LAUNDRY
    // ═════════════════════════════════════════════════════════════════════════
    Route::middleware('role:super_admin,admin_toko')
        ->prefix('laundry')->name('laundry.')->group(function () {
            // Static routes dulu
            Route::get('/',         [LaundryController::class, 'index'])->name('index');
            Route::get('/menunggu', [LaundryController::class, 'menungguLaundry'])->name('menunggu');
            Route::get('/dalam',    [LaundryController::class, 'dalamLaundry'])->name('dalam');
            Route::get('/siap',     [LaundryController::class, 'siapDisewakan'])->name('siap');
            Route::get('/riwayat',  [LaundryController::class, 'riwayat'])->name('riwayat');
            // Batch AJAX — harus sebelum /{laundry}
            Route::post('/batch/mulai',   [LaundryController::class, 'batchMulaiLaundry'])->name('batch.mulai');
            Route::post('/batch/selesai', [LaundryController::class, 'batchSelesaiLaundry'])->name('batch.selesai');
            // Wildcard paling bawah
            Route::get('/{laundry}',             [LaundryController::class, 'show'])->name('show');
            Route::post('/{laundry}/mulai',      [LaundryController::class, 'mulaiLaundry'])->name('mulai');
            Route::post('/{laundry}/selesai',    [LaundryController::class, 'selesaiLaundry'])->name('selesai');
        });

    // API stats laundry (realtime badge)
    Route::middleware('role:super_admin,admin_toko')
        ->get('/api/laundry/stats', function () {
            return response()->json([
                'menunggu_laundry' => \App\Models\Laundry::menungguLaundry()->count(),
                'dalam_laundry'    => \App\Models\Laundry::dalamLaundry()->count(),
                'siap_disewakan'   => \App\Models\Laundry::siapDisewakan()->count(),
            ]);
        })->name('api.laundry.stats');

    // ═════════════════════════════════════════════════════════════════════════
    // CUSTOMER
    // ═════════════════════════════════════════════════════════════════════════
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/',       [CustomerController::class, 'index'])->name('index');
        Route::get('/search', [CustomerController::class, 'search'])->name('search');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/',      [CustomerController::class, 'store'])->name('store');
        Route::get('/check-duplicate', [CustomerController::class, 'checkDuplicate'])->name('check-duplicate');

        // ── STATIC routes WAJIB sebelum wildcard /{customer} ─────────────────
        Route::middleware('role:super_admin,admin_toko')->group(function () {
            Route::get('/export',  [CustomerController::class, 'export'])->name('export');
            Route::get('/archive', [CustomerController::class, 'archive'])->name('archive');
        });

        // ── Wildcard /{customer} — taruh paling bawah ────────────────────────
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');

        Route::middleware('role:super_admin,admin_toko')->group(function () {
            Route::get('/{customer}/edit',        [CustomerController::class, 'edit'])->name('edit');
            Route::patch('/{customer}',           [CustomerController::class, 'update'])->name('update');
            Route::patch('/{customer}/blacklist', [CustomerController::class, 'toggleBlacklist'])->name('blacklist');
            Route::post('/{id}/restore',          [CustomerController::class, 'restore'])->name('restore');
        });

        Route::middleware('role:super_admin,admin_toko')
            ->delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');

        // Force delete (permanent) - only super_admin
        // FIX: tambahkan ->withTrashed() — target route ini adalah customer
        // yang SUDAH soft-deleted (misal dari halaman Arsip > Terhapus
        // Sementara). Tanpa withTrashed(), implicit route model binding
        // Laravel akan 404 karena secara default query binding hanya
        // mencari customer yang belum di-soft-delete.
        Route::middleware('role:super_admin')
            ->delete('/{customer}/force', [CustomerController::class, 'forceDestroy'])->name('destroy.force')
            ->withTrashed();
    });

    Route::middleware('role:super_admin,admin_toko')->group(function () {
        Route::resource('broadcasts', BroadcastController::class)->except(['show']);
        Route::post('broadcasts/{broadcast}/send-now', [BroadcastController::class, 'sendNow'])->name('broadcasts.send-now');
        Route::post('broadcasts/{broadcast}/clear-logs', [BroadcastController::class, 'clearLogs'])->name('broadcasts.clear-logs');
        Route::post('broadcasts/send-selected', [BroadcastController::class, 'sendSelected'])->name('broadcasts.send-selected');
        Route::get('broadcasts/{broadcast}/logs', [BroadcastController::class, 'logs'])->name('broadcasts.logs');
    });

    // ═════════════════════════════════════════════════════════════════════════
    // PRODUK
    // ═════════════════════════════════════════════════════════════════════════
    Route::middleware('role:super_admin,admin_toko')
        ->prefix('products')->name('products.')->group(function () {
            Route::get('/',       [ProductController::class, 'index'])->name('index');
            Route::get('/create', [ProductController::class, 'create'])->name('create');
            Route::post('/',      [ProductController::class, 'store'])->name('store');

            // FIX: route spesifik harus SEBELUM route wildcard /{product}
            Route::get('/{product}/qr-download', [ProductController::class, 'downloadQr'])->name('qr.download');
            Route::get('/{product}/scan-info',   [ProductController::class, 'scanInfo'])->name('scan-info');
            Route::get('/{product}/edit',        [ProductController::class, 'edit'])->name('edit');
            Route::post('/{product}/qr',         [ProductController::class, 'regenerateQr'])->name('qr');

            Route::get('/{product}',    [ProductController::class, 'show'])->name('show');
            Route::patch('/{product}',  [ProductController::class, 'update'])->name('update');
            Route::put('/{product}',    [ProductController::class, 'update']);
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
        });

    // ═════════════════════════════════════════════════════════════════════════
    // KATEGORI
    // ═════════════════════════════════════════════════════════════════════════
    Route::middleware('role:super_admin')
        ->prefix('categories')->name('categories.')->group(function () {
            Route::get('/',             [CategoryController::class, 'index'])->name('index');
            Route::get('/create',       [CategoryController::class, 'create'])->name('create');
            Route::post('/',            [CategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit',  [CategoryController::class, 'edit'])->name('edit');
            Route::patch('/{category}',     [CategoryController::class, 'update'])->name('update');
            Route::delete('/{category}',    [CategoryController::class, 'destroy'])->name('destroy');
        });

    // ═════════════════════════════════════════════════════════════════════════
    // PAKET SEWA — FIX: gunakan middleware alias 'role' (bukan double 'auth','role')
    // ═════════════════════════════════════════════════════════════════════════
    Route::middleware('role:super_admin')
        ->prefix('packages')->name('packages.')->group(function () {
            Route::get('/',                          [PackageController::class, 'index'])->name('index');
            Route::get('/create',                    [PackageController::class, 'create'])->name('create');
            Route::post('/',                         [PackageController::class, 'store'])->name('store');
            Route::get('/{package}/edit',            [PackageController::class, 'edit'])->name('edit');
            Route::put('/{package}',                 [PackageController::class, 'update'])->name('update');
            Route::delete('/{package}',              [PackageController::class, 'destroy'])->name('destroy');
            Route::get('/{package}/penalty-preview', [PackageController::class, 'penaltyPreview'])->name('penalty-preview');
        });

    // ═════════════════════════════════════════════════════════════════════════
    // CABANG
    // ═════════════════════════════════════════════════════════════════════════
    Route::middleware('role:super_admin')
        ->prefix('branches')->name('branches.')->group(function () {
            Route::get('/',             [BranchController::class, 'index'])->name('index');
            Route::get('/create',       [BranchController::class, 'create'])->name('create');
            Route::post('/',            [BranchController::class, 'store'])->name('store');
            Route::get('/{branch}',     [BranchController::class, 'show'])->name('show');
            Route::get('/{branch}/edit',[BranchController::class, 'edit'])->name('edit');
            Route::patch('/{branch}',   [BranchController::class, 'update'])->name('update');
            Route::delete('/{branch}',  [BranchController::class, 'destroy'])->name('destroy');
        });

    // ═════════════════════════════════════════════════════════════════════════
    // PENGGUNA
    // ═════════════════════════════════════════════════════════════════════════
    Route::middleware('role:super_admin')
        ->prefix('users')->name('users.')->group(function () {
            Route::get('/',              [UserController::class, 'index'])->name('index');
            Route::get('/create',        [UserController::class, 'create'])->name('create');
            Route::post('/',             [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit',   [UserController::class, 'edit'])->name('edit');
            Route::patch('/{user}',      [UserController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle',[UserController::class, 'toggle'])->name('toggle');
            Route::delete('/{user}',     [UserController::class, 'destroy'])->name('destroy');
        });

    // ═════════════════════════════════════════════════════════════════════════
    // LAPORAN
    // ═════════════════════════════════════════════════════════════════════════
    Route::middleware('role:super_admin,admin_toko')
        ->prefix('reports')->name('reports.')->group(function () {
            Route::get('/revenue',            [ReportController::class, 'revenue'])->name('revenue');
            Route::get('/transactions',       [ReportController::class, 'transactions'])->name('transactions');
            Route::get('/stock',              [ReportController::class, 'stock'])->name('stock');
            Route::get('/returns',            [ReportController::class, 'returns'])->name('returns');
            Route::get('/outstanding',        [ReportController::class, 'outstanding'])->name('outstanding');
            Route::get('/export/excel',       [ReportController::class, 'exportExcel'])->name('export.excel');
            Route::get('/export/pdf',         [ReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/returns/export-pdf', [ReportController::class, 'exportReturnsPdf'])->name('returns.pdf');
        });

    // ═════════════════════════════════════════════════════════════════════════
    // AUDIT LOG — FIX: namespace Admin + route benar
    // ═════════════════════════════════════════════════════════════════════════
    Route::middleware('role:super_admin')
        ->prefix('admin/audit-logs')->name('audit.')->group(function () {
            Route::get('/',       [AuditLogController::class, 'index'])->name('index');
            Route::get('/{log}',  [AuditLogController::class, 'show'])->name('show');
        });

});
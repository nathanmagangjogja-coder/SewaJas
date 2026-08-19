<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class ProductController extends Controller
{
   public function index(Request $request)
{
    $user = Auth::user();
    $query = Product::with(['category', 'branch'])
        ->when(!$user->isSuperAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
        // FIX: filter cabang di form sebelumnya tidak pernah diterapkan ke query
        // (cuma tampil di dropdown tapi $request->branch tidak dipakai sama sekali).
        ->when($user->isSuperAdmin() && $request->branch, fn($q) => $q->where('branch_id', $request->branch))
        ->when($request->category, fn($q) => $q->where('category_id', $request->category))
        ->when($request->status, fn($q) => $q->where('status', $request->status))
        ->when($request->size, fn($q) => $q->where('size', $request->size))
        ->when(
            $request->search,
            fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%")
                    ->orWhere('color', 'like', "%{$request->search}%");
            }),
        )
        ->latest();

    $products   = $query->paginate(16)->withQueryString();
    $categories = Category::where('is_active', true)->get();
    // FIX: $branches sebelumnya tidak dikirim ke view, padahal dipakai
    // dropdown filter "Cabang" untuk Superadmin → Undefined variable $branches.
    $branches = $user->isSuperAdmin()
        ? \App\Models\Branch::where('is_active', true)->orderBy('name')->get()
        : collect();

    return view('products.index', compact('products', 'categories', 'branches'));
}

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        // FIX: $branches sebelumnya tidak dikirim ke view, padahal dipakai
        // untuk pilih cabang tujuan produk baru → Undefined variable $branches.
        $branches = Auth::user()->isSuperAdmin()
            ? \App\Models\Branch::where('is_active', true)->orderBy('name')->get()
            : collect();
        return view('products.create', compact('categories', 'branches'));
    }

public function store(Request $request)
{
    // default set timezone to Asia/Jakarta
    date_default_timezone_set('Asia/Jakarta');

    $user = Auth::user();

    $data = $request->validate([
        'category_id'   => 'required|exists:categories,id',
        'name'          => 'required|string|max:150',
        'description'   => 'nullable|string',
        'size'          => 'nullable|string|max:20',
        'color'         => 'nullable|string|max:50',
        'brand'         => 'nullable|string|max:100',
        'rental_price'  => 'required|numeric|min:0',
        'deposit_price' => 'nullable|numeric|min:0',
        'stock_total'   => 'required|integer|min:1',
        'condition'     => 'required|in:excellent,good,fair,poor',
        'status'        => 'required|in:available,maintenance,inactive',
        'notes'         => 'nullable|string',
        'image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        // Multi-cabang (checkbox "Cabang") — WAJIB pilih minimal 1 untuk
        // Superadmin. Role lain tidak mengirim field ini sama sekali,
        // otomatis dibuat di cabangnya sendiri.
        'branch_ids'    => $user->isSuperAdmin() ? 'required|array|min:1' : 'nullable|array',
        'branch_ids.*'  => 'exists:branches,id',
    ]);

    // Tentukan cabang tujuan: Superadmin bisa centang lebih dari satu
    // (produk yang sama dibuat di semua cabang tsb sekaligus). Role lain
    // otomatis memakai cabangnya sendiri, seperti semula.
    $branchIds = $user->isSuperAdmin()
        ? collect($data['branch_ids'])->unique()->values()
        : collect([$user->branch_id ?? 1]);

    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('products', 'public');
    }

    unset($data['image'], $data['branch_ids']);

    // ── BUAT PRODUK DI SETIAP CABANG YANG DICENTANG ────────────────────────
    // Semua field (nama, harga, deskripsi, foto, dll) sama persis; yang
    // dibuat sendiri-sendiri per cabang hanyalah kode produk, QR code, dan
    // stok (stok fisik tidak masuk akal dibagi lintas cabang).
    $firstProduct  = null;
    $createdCount  = 0;

    foreach ($branchIds as $branchId) {
        $payload = $data;
        $payload['branch_id']       = $branchId;
        $payload['stock_available'] = $data['stock_total'];
        $payload['code']            = $this->generateCode($branchId);
        $payload['photo']           = $imagePath;

        $product = Product::create($payload);
        $this->generateQrCode($product);

        $firstProduct ??= $product;
        $createdCount++;
    }

    $message = $createdCount > 1
        ? "Produk berhasil ditambahkan ke {$createdCount} cabang sekaligus!"
        : 'Produk berhasil ditambahkan!';

    return redirect()->route('products.show', $firstProduct)
        ->with('success', $message);
}

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'size' => 'nullable|string|max:20',
            'color' => 'nullable|string|max:50',
            'brand' => 'nullable|string|max:100',
            'rental_price' => 'required|numeric|min:0',
            'deposit_price' => 'nullable|numeric|min:0',
            'condition' => 'required|in:excellent,good,fair,poor',
            'status' => 'required|in:available,maintenance,inactive',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            // Multi-cabang (khusus Superadmin) — checkbox di menu "Cabang".
            // Cabang produk ini sendiri SELALU ikut terkirim lewat hidden input
            // di blade, jadi array minimal berisi 1 (cabang produk ini).
            'branch_ids'   => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        if ($request->hasFile('image')) {
            // Hapus foto lama jika ada
            if ($product->photo && Storage::disk('public')->exists($product->photo)) {
                Storage::disk('public')->delete($product->photo);
            }
            // Simpan foto baru ke kolom 'photo'
            $data['photo'] = $request->file('image')->store('products', 'public');
        } else {
            // Tidak ada upload baru, jangan overwrite foto lama
            unset($data['photo']);
        }

        // Cabang TAMBAHAN yang dicentang Superadmin (di luar cabang produk ini
        // sendiri) — dipakai untuk MENGGANDAKAN produk, BUKAN memindahkan
        // produk yang sedang diedit. Produk asal tetap di cabangnya semula.
        $extraBranchIds = collect($data['branch_ids'] ?? [])
            ->reject(fn ($id) => (int) $id === (int) $product->branch_id)
            ->unique()
            ->values();

        // Hapus key yang bukan kolom tabel products sebelum update() produk asal.
        unset($data['image'], $data['branch_ids']);

        $product->update($data);

        // ── GANDAKAN PRODUK KE SETIAP CABANG LAIN YANG DICENTANG ───────────
        // Nama, harga, foto, kategori, dll disalin persis dari produk yang baru
        // saja diupdate. Kode produk, QR code, dan stok dibuat baru per cabang
        // (tidak masuk akal berbagi stok fisik lintas cabang).
        $duplicatedCount = 0;
        foreach ($extraBranchIds as $branchId) {
            $clone = $product->replicate(['code', 'qr_code']);
            $clone->branch_id       = $branchId;
            $clone->code            = $this->generateCode($branchId);
            $clone->stock_total     = $product->stock_total;
            $clone->stock_available = $product->stock_total; // stok baru = penuh, belum ada yg disewa di cabang ini
            $clone->save();

            $this->generateQrCode($clone);
            $duplicatedCount++;
        }

        $message = 'Produk berhasil diperbarui!';
        if ($duplicatedCount > 0) {
            $message .= " Produk yang sama juga otomatis dibuat di {$duplicatedCount} cabang lain.";
        }

        return redirect()->route('products.show', $product)->with('success', $message);
    }
    public function destroy($id)
    {
        // Cari produk berdasarkan ID
        $product = \App\Models\Product::findOrFail($id);
        
        // Hapus produk
        $product->delete();
        
        // Redirect kembali ke daftar produk
        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus!');
    }
    public function show(Product $product)
    {
        // $this->authorize('view', $product);
        $product->load(['category', 'branch']);

        // FIX: sebelumnya cuma load relasi 'rentalItems' ke $product tapi TIDAK
        // PERNAH mengirim variabel $rentals ke view — padahal blade-nya butuh
        // $rentals (lihat @if(isset($rentals)) di products/show.blade.php),
        // jadi section "Riwayat Rental" selalu tampil kosong walau produk ini
        // sudah pernah disewa berkali-kali.
        //
        // Riwayat rental produk didapat lewat tabel rental_items (satu Rental
        // bisa berisi banyak produk), bukan relasi langsung Product → Rental.
        $rentals = \App\Models\Rental::with('customer')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest('rental_date')
            ->paginate(10);

        return view('products.show', compact('product', 'rentals'));
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);
        $categories = Category::where('is_active', true)->get();
        // Dipakai untuk checklist multi-cabang (lihat update() di bawah).
        $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        return view('products.edit', compact('product', 'categories', 'branches'));
    }

    public function regenerateQr(Product $product)
    {
        $this->authorize('update', $product);
        $this->generateQrCode($product);
        return back()->with('success', 'QR Code berhasil digenerate ulang!');
    }

    public function downloadQr(Product $product)
    {
        if (!$product->qr_code || !Storage::disk('public')->exists($product->qr_code)) {
            return back()->with('error', 'File QR Code tidak ditemukan.');
        }
        
        return Storage::disk('public')->download($product->qr_code);
    }

    private function generateCode(int $branchId): string
    {
        $branchId = $branchId ?? 1;
        $prefix = 'PRD' . str_pad($branchId, 2, '0', STR_PAD_LEFT);
        
        // Cari kode terakhir yang depannya sama dengan prefix
        $lastProduct = Product::where('code', 'like', "{$prefix}%")
            ->orderBy('code', 'desc') // Urutkan berdasarkan code, bukan ID
            ->first();

        // Jika ada produk, ambil 4 angka terakhir, jika tidak ada, mulai dari 1
        $seq = $lastProduct ? (int) substr($lastProduct->code, -4) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function generateQrCode(Product $product): void
    {
        $qrData = route('products.show', $product);
        $path = 'qrcodes/products/' . $product->code . '.svg';
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $svg = QrCode::format('svg')->size(200)->generate($qrData);
        file_put_contents($fullPath, $svg);
        $product->update(['qr_code' => $path]);
    }

    /**
     * API: Informasi produk untuk halaman Scan QR.
     * Dipanggil via fetch() dari scan.blade.php saat QR produk di-scan.
     *
     * GET /products/{product}/scan-info
     * Response: JSON info produk (nama, stok, harga, status)
     */
    public function scanInfo(Product $product)
    {
        $product->load('category');

        return response()->json([
            'id'              => $product->id,
            'name'            => $product->name,
            'code'            => $product->code,
            'size'            => $product->size,
            'color'           => $product->color,
            'rental_price'    => $product->rental_price,
            'stock_available' => $product->stock_available,
            'stock_total'     => $product->stock_total,
            'status'          => $product->status,
            'category'        => $product->category?->name,
            'photo_url'       => $product->photo ? asset('storage/' . $product->photo) : null,
        ]);
    }
}
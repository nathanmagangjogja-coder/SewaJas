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

    return view('products.index', compact('products', 'categories'));
}

    public function create()
    {
       
        $categories = Category::where('is_active', true)->get();
        return view('products.create', compact('categories'));
    }

public function store(Request $request)
{
    // default set timezone to Asia/Jakarta
    date_default_timezone_set('Asia/Jakarta');
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
    ]);

    $user     = Auth::user();
    $branchId = $user->branch_id ?? 1;

    $data['branch_id']       = $branchId; // ✅ bukan $user->branch_id
    $data['stock_available'] = $data['stock_total'];
    $data['code']            = $this->generateCode($branchId);

    if ($request->hasFile('image')) {
        $data['photo'] = $request->file('image')->store('products', 'public');
    }

    unset($data['image']);

    $product = Product::create($data);
    $this->generateQrCode($product);

    return redirect()->route('products.show', $product)
        ->with('success', 'Produk berhasil ditambahkan!');
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

        // Hapus key 'image' dari $data agar tidak masuk ke kolom DB
        unset($data['image']);

        $product->update($data);

        return redirect()->route('products.show', $product)->with('success', 'Produk berhasil diperbarui!');
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
        $product->load(['category', 'branch', 'rentalItems' => fn($q) => $q->with('rental.customer')->latest()->limit(10)]);
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);
        $categories = Category::where('is_active', true)->get();
        return view('products.edit', compact('product', 'categories'));
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
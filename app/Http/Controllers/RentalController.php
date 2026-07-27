<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\RentalPackage;
use App\Services\RentalService;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RentalController extends Controller
{
    protected RentalService $rentalService;
    protected NotificationService $notifService;
    protected WhatsAppMessageService $waService;

    public function __construct(
        RentalService $rentalService,
        NotificationService $notifService,
        WhatsAppMessageService $waService
    ) {
        $this->rentalService = $rentalService;
        $this->notifService  = $notifService;
        $this->waService     = $waService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Rental::with(['customer', 'items', 'createdBy', 'package'])
            ->whereHas('customer')
            ->when(!$user->isSuperAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->status, fn($q) => $q->where('rental_status', $request->status))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('invoice_number', 'like', "%{$request->search}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"));
            }))
            ->when($request->date_from, fn($q) => $q->whereDate('rental_date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('rental_date', '<=', $request->date_to))
            ->latest();

        $rentals      = $query->paginate(15)->withQueryString();
        $statusCounts = $this->getStatusCounts($user);

        return view('rentals.index', compact('rentals', 'statusCounts'));
    }

    public function create()
    {
        $user = Auth::user();

        $customers = Customer::where('branch_id', $user->branch_id)
            ->where('is_blacklisted', false)
            ->withCount('rentals')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'phone'         => $c->phone,
                'photo'         => $c->photo_url,
                'nik'           => $c->id_number,
                'id_photo'      => $c->id_photo ? asset('storage/' . $c->id_photo) : null,
                'notes'         => $c->notes,
                // Dipakai frontend untuk membedakan pesan "customer baru" (belum
                // pernah menyewa) vs "customer lama" yang kebetulan belum punya
                // foto jaminan tersimpan (mis. transaksi lamanya belum lengkap).
                'rental_count'  => $c->rentals_count,
            ]);

        $categories = Category::where('is_active', true)->withCount('products')->get();

        $products = Product::with('category')
            ->where('branch_id', $user->branch_id)
            ->where('status', 'available')
            ->where('stock_available', '>', 0)
            ->orderBy('name')
            ->get();

        $durationDays = RentalService::DURATION_DAYS;

        $packages = RentalPackage::active()->get();
        $defaultPkg = $packages->firstWhere('duration_days', 3) ?? $packages->first();

        return view('rentals.create', compact('customers', 'categories', 'products', 'durationDays', 'packages', 'defaultPkg'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'           => 'required|exists:customers,id',
            'rental_date'           => 'required|date',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'guarantee.type'        => 'required|string',
            'guarantee.id_name'     => 'nullable|string',
            'guarantee.id_number'   => 'nullable|string',
            'guarantee.description' => 'nullable|string',
            'discount'              => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
            'id_photo'              => 'nullable|image|max:2048',
            'id_number'             => ['nullable', 'string', 'max:50', 'regex:/^[0-9]*$/'],
            'customer_notes'        => 'nullable|string',
        ], [
            'id_number.regex' => 'Nomor KTP hanya boleh berisi angka.',
        ]);

        // ── Verifikasi KTP customer ──────────────────────────────────────────
        // Customer LAMA (sudah punya foto KTP tersimpan) boleh pakai foto lama.
        // Customer BARU (belum punya foto KTP di database) WAJIB upload foto KTP
        // + nomor KTP saat ini, saat transaksi penyewaan dibuat.
        //
        $customer = Customer::findOrFail($request->customer_id);

        $guaranteeType = $request->input('guarantee.type');
        $photoRequired = in_array($guaranteeType, ['ktp', 'sim'], true);

        if ($photoRequired && !$customer->id_photo && !$request->hasFile('id_photo')) {
            return back()->withInput()->withErrors([
                'id_photo' => 'Customer ini belum memiliki foto jaminan tersimpan. Foto wajib diunggah untuk penyewaan pertama dengan jaminan ' . strtoupper($guaranteeType) . '.',
            ]);
        }

        if ($request->filled('id_number')) {
            $normId = preg_replace('/[^0-9A-Za-z]/', '', $request->id_number);
            $dupe = Customer::withTrashed()
                ->where('id', '!=', $customer->id)
                ->whereRaw("REGEXP_REPLACE(COALESCE(id_number, ''), '[^0-9A-Za-z]', '') = ?", [$normId])
                ->exists();

            if ($dupe) {
                return back()->withInput()->withErrors([
                    'id_number' => 'Nomor KTP ini sudah terdaftar pada customer lain.',
                ]);
            }
        }

        // Simpan/replace foto & nomor KTP ke record Customer supaya bisa dipakai
        // ulang di transaksi berikutnya ("customer lama").
        //
        $newIdPhotoPath = null;
        $oldIdPhotoPath = $customer->id_photo;

        $rental = DB::transaction(function () use ($request, $customer, &$newIdPhotoPath, $oldIdPhotoPath) {
            if ($request->hasFile('id_photo')) {
                $newIdPhotoPath = $request->file('id_photo')->store('customers/id-photos', 'public');
                $customer->id_photo = $newIdPhotoPath;
            }
            if ($request->filled('id_number')) {
                $customer->id_number = $request->id_number;
            }
            if ($request->filled('customer_notes')) {
                $customer->notes = $request->customer_notes;
            }
            if ($customer->isDirty()) {
                $customer->save();
            }

            // File & field KTP sudah disimpan langsung ke Customer di atas — tidak
            // perlu diteruskan lagi ke RentalService.
            return $this->rentalService->createRental($request->except(['id_photo', 'customer_notes']));
        });

        // Baru hapus foto KTP lama SETELAH transaksi sukses (di luar transaksi,
        // karena penghapusan file tidak bisa di-rollback oleh DB::transaction).
        if ($newIdPhotoPath && $oldIdPhotoPath) {
            try { Storage::disk('public')->delete($oldIdPhotoPath); } catch (\Throwable $e) {}
        }

        $targetIds = \App\Models\User::where('role', 'super_admin')
            ->orWhere(fn($q) => $q->where('branch_id', $rental->branch_id)
                ->where('role', 'admin_toko'))
            ->pluck('id')
            ->toArray();

        $this->notifService->rentalCreated($rental, $targetIds);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', "Penyewaan {$rental->invoice_number} berhasil dibuat!");
    }

    public function show(Rental $rental)
    {
        $this->authorize('view', $rental);
        $rental->load(['customer', 'items.product', 'guarantees', 'payments.receivedBy', 'createdBy', 'branch', 'package']);

        return view('rentals.show', compact('rental'));
    }

    public function processPayment(Request $request, Rental $rental)
    {
        $this->authorize('update', $rental);

        $request->validate([
            'amount'           => 'required|numeric|min:1',
            'method'           => 'required|in:cash,transfer,qris,other',
            // Bank/e-wallet: wajib untuk transfer & qris, tidak dipakai untuk cash.
            'payment_channel'  => 'nullable|required_if:method,transfer|required_if:method,qris|string|max:50',
            // Nomor rekening: wajib HANYA untuk transfer (QRIS tidak butuh nomor rekening).
            'account_number'   => 'nullable|required_if:method,transfer|string|max:50',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $payment = $this->rentalService->processPayment($rental, $request->all());

        return back()->with('success', "Pembayaran {$payment->payment_number} berhasil dicatat!");
    }

    /**
     * Proses pengembalian barang per-item dari modal show.blade.php.
     * Form mengirim: items[i][rental_item_id], items[i][condition], items[i][notes]
     * Nilai condition: good | damaged | lost
     */
    public function processReturn(Request $request, Rental $rental)
    {
        $this->authorize('update', $rental);

        if (!in_array($rental->rental_status, ['active', 'overdue'])) {
            return back()->with('error', 'Penyewaan ini tidak dapat diproses pengembalian.');
        }

        // ── Validasi sesuai struktur form di show.blade.php & scan-result.blade.php ───────────
        $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.rental_item_id'    => 'required|exists:rental_items,id',
            'items.*.condition'         => 'required|in:good,damaged,lost',
            'items.*.notes'             => 'nullable|string|max:1000',
            'items.*.penalty_resolution' => 'nullable|required_if:items.*.condition,damaged|required_if:items.*.condition,lost|in:charge_double,claim_guarantee',
            'discount_name'          => 'nullable|string|max:255',
            'discount_description'   => 'nullable|string|max:1000',
            'discount_type'          => 'nullable|in:nominal,percent',
            'discount_value'         => 'nullable|numeric|min:0',
        ]);

        $wantsGuaranteeClaim = collect($request->items)
            ->contains(fn($item) => ($item['penalty_resolution'] ?? null) === 'claim_guarantee');

        if ($wantsGuaranteeClaim && !$rental->guarantees()->where('status', 'held')->exists()) {
            return back()
                ->withErrors(['penalty' => 'Tidak ada jaminan yang bisa disita untuk transaksi ini.'])
                ->withInput();
        }

        // Perhitungan nilai diskon akhir (nominal vs persen) dilakukan DI DALAM
        // RentalService::processReturn(), karena rumus persen butuh $lateFee yang
        // baru diketahui pasti setelah dihitung di sana. Di sini hanya diteruskan
        // input mentahnya supaya tidak ada dua sumber perhitungan late fee yang
        // bisa saling berbeda (di controller vs di service).
        $rental = $this->rentalService->processReturn($rental, [
            'items'                 => $request->items,
            'discount_name'         => $request->discount_name,
            'discount_description'  => $request->discount_description,
            'discount_type'         => $request->discount_type,
            'discount_value'        => $request->discount_value,
        ]);

        return redirect()
            ->route('rentals.scan.show', $rental->invoice_number)
            ->with('success', "Pengembalian {$rental->invoice_number} berhasil diproses!");
    }

        public function updateDiscount(Request $request, Rental $rental)
    {
        $this->authorize('update', $rental);

        $user = Auth::user();
        if (!in_array($user->role, ['admin_toko', 'super_admin'])) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah diskon.');
        }

        if (in_array($rental->rental_status, ['returned', 'cancelled'])) {
            return back()->with('error', 'Diskon tidak dapat diubah untuk transaksi yang sudah selesai/dibatalkan.');
        }

        $data = $request->validate([
            'discount_mode'    => 'required|in:amount,percent',
            'discount_amount'  => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_reason'  => 'nullable|string|max:500',
        ]);

        $subtotal = (float) $rental->subtotal;

        $discount = $data['discount_mode'] === 'percent'
            ? round($subtotal * (((float) ($data['discount_percent'] ?? 0)) / 100), 2)
            : (float) ($data['discount_amount'] ?? 0);

        // Diskon tidak boleh melebihi subtotal
        $discount = min($discount, $subtotal);

        $oldDiscount = (float) $rental->discount;

        // total_amount = subtotal - discount + late_fee, konsisten dengan nota/invoice/PDF/thermal
        // yang semuanya membaca dari kolom total_amount hasil hitungan ini.
        $rental->update([
            'discount'     => $discount,
            'total_amount' => max(0, $subtotal - $discount + (float) $rental->late_fee),
        ]);

        // Catat ke ActivityLog untuk audit — sesuaikan nama kolom dengan skema
        // ActivityLog yang sebenarnya (file model-nya belum tersedia saat prompt ini dibuat).
        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::create([
                'user_id'     => $user->id,
                'action'      => 'update_discount',
                'description' => sprintf(
                    'Mengubah diskon transaksi %s dari Rp %s menjadi Rp %s. Alasan: %s',
                    $rental->invoice_number,
                    number_format($oldDiscount, 0, ',', '.'),
                    number_format($discount, 0, ',', '.'),
                    $data['discount_reason'] ?? '-'
                ),
                'subject_type' => Rental::class,
                'subject_id'   => $rental->id,
            ]);
        }

        return back()->with('success', "Diskon untuk {$rental->invoice_number} berhasil diperbarui.");
    }

    public function edit(Rental $rental)
    {
        $this->authorize('update', $rental);

        $user = Auth::user();
        $customers = Customer::where('branch_id', $rental->branch_id)
            ->orderBy('name')
            ->get();
        $products = Product::with('category')
            ->where('branch_id', $rental->branch_id)
            ->orderBy('name')
            ->get();
        $rental->load(['customer', 'items.product', 'guarantees', 'package']);

        return view('rentals.edit', compact('rental', 'customers', 'products'));
    }

    public function update(Request $request, Rental $rental)
    {
        $this->authorize('update', $rental);

        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'rental_date' => 'required|date',
            'discount'    => 'nullable|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        // NOTE: kalau ada field lain yang perlu diupdate (mis. items, guarantee),
        // sebaiknya delegasikan ke RentalService (seperti createRental()) supaya
        // logika hitung ulang total_amount/subtotal konsisten di satu tempat.
        $rental->update($data);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', "Penyewaan {$rental->invoice_number} berhasil diperbarui!");
    }

    public function cancel(Request $request, Rental $rental)
    {
        $this->authorize('cancel', $rental);

        if (in_array($rental->rental_status, ['returned', 'cancelled'])) {
            return back()->with('error', 'Penyewaan ini tidak dapat dibatalkan.');
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        // NOTE: asumsi kolom 'cancel_reason', 'cancelled_at', 'cancelled_by' BELUM
        // tentu ada di tabel rentals. Kalau migration belum punya kolom ini,
        // hapus baris terkait di bawah atau tambahkan migration-nya dulu.
        $rental->update([
            'rental_status' => 'cancelled',
            'cancel_reason' => $request->reason,
            'cancelled_at'  => now(),
            'cancelled_by'  => Auth::id(),
        ]);

        return redirect()
            ->route('rentals.show', $rental)
            ->with('success', "Penyewaan {$rental->invoice_number} berhasil dibatalkan.");
    }

    public function destroy(Rental $rental)
    {
        $this->authorize('delete', $rental); // RentalPolicy: super_admin only

        $invoiceNumber = $rental->invoice_number;
        $rental->delete();

        return redirect()
            ->route('rentals.index')
            ->with('success', "Penyewaan {$invoiceNumber} berhasil dihapus.");
    }

    public function scanQr(string $invoice)
    {
        $rental = Rental::where('invoice_number', $invoice)
            ->with(['customer', 'items.product', 'guarantees', 'returnRecord', 'package'])
            ->firstOrFail();

        $feeData = in_array($rental->rental_status, ['active', 'overdue'])
            ? ['late_days' => $rental->live_late_days, 'late_fee' => $rental->live_late_fee]
            : ['late_days' => 0, 'late_fee' => 0];

        return view('rentals.scan-result', compact('rental', 'feeData'));
    }

    public function scanPage()
    {
        return view('rentals.scan');
    }

    public function invoice(Rental $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['customer', 'items.product', 'guarantees', 'branch', 'createdBy', 'package']);
        $qrCode = base64_encode(QrCode::format('svg')->size(100)->generate(route('rentals.show', $rental->id)));
        return view('rentals.invoice', compact('rental', 'qrCode'));
    }

    public function thermalPrint(Rental $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['customer', 'items.product', 'branch', 'package']);
        $qrCode = base64_encode(QrCode::format('svg')->size(80)->generate(route('rentals.show', $rental->id)));
        return view('rentals.thermal', compact('rental', 'qrCode'));
    }

    public function exportPdf(Rental $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['customer', 'items.product', 'guarantees', 'branch', 'createdBy', 'package']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rentals.pdf', compact('rental'));
        return $pdf->download("invoice-{$rental->invoice_number}.pdf");
    }

    public function invoicePublic(string $token)
    {
        $rental = Rental::where('public_token', $token)
            ->with(['customer', 'items.product', 'guarantees', 'branch', 'createdBy'])
            ->firstOrFail();
        return view('rentals.invoice-public', compact('rental'));
    }

        public function invoicePdfPublic(string $token)
    {
        $rental = Rental::where('public_token', $token)
            ->with(['customer', 'items.product', 'guarantees', 'branch', 'createdBy', 'package'])
            ->firstOrFail();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rentals.pdf', compact('rental'));
        return $pdf->stream("invoice-{$rental->invoice_number}.pdf");
    }

    // REFACTOR: buildWhatsAppMessage()/buildReminderMessage() dan logika
    // normalisasi nomor HP (sebelumnya diduplikasi persis di whatsapp() &
    // sendReminder()) dipindahkan ke App\Services\WhatsAppMessageService.
    // Memangkas ~105 baris dari controller ini dan menghilangkan duplikasi.
    public function whatsapp(Rental $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['customer', 'items.product']);

        $pdfLink = route('rentals.invoice.pdf.public', $rental->public_token);
        $message = $this->waService->buildPdfInvoiceMessage($rental, $pdfLink);

        return redirect()->away($this->waService->buildWaLink($rental, $message));
    }

    public function sendReminder(Rental $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['customer', 'items.product']);
        $message = $this->waService->buildReminderMessage($rental);

        return redirect()->away($this->waService->buildWaLink($rental, $message));
    }

    public function downloadQr(Rental $rental)
    {
        $this->authorize('view', $rental);

        if (!$rental->qr_code) {
            return back()->with('error', 'QR Code tidak ditemukan');
        }
        $filePath = storage_path('app/public/' . $rental->qr_code);
        if (!file_exists($filePath)) {
            return back()->with('error', 'File QR Code tidak ditemukan');
        }
        return response()->download($filePath, 'QR-' . $rental->invoice_number . '.svg');
    }

    private function getStatusCounts($user): array
    {
        $query = Rental::query()
            ->whereHas('customer') // konsisten dgn index(): rental customer terhapus disembunyikan
            ->when(!$user->isSuperAdmin(), fn($q) => $q->where('branch_id', $user->branch_id));

        return [
            'all'       => (clone $query)->count(),
            'waiting'   => (clone $query)->where('rental_status', 'waiting')->count(),
            'active'    => (clone $query)->where('rental_status', 'active')->count(),
            'overdue'   => (clone $query)->where('rental_status', 'overdue')->count(),
            'returned'  => (clone $query)->where('rental_status', 'returned')->count(),
            'cancelled' => (clone $query)->where('rental_status', 'cancelled')->count(),
        ];
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\RentalPackage;
use App\Models\Branch;
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
            ->when($request->status, function ($q) use ($request) {
                // "today" dari kartu dashboard "Penyewaan Masuk · Hari Ini" berarti
                // "dibuat/masuk hari ini" (filter tanggal), BUKAN rental_status.
                // Sebelumnya query ini memperlakukan 'today' sebagai nilai
                // rental_status (yang tidak valid) -> hasil selalu kosong ->
                // memicu tampilan empty-state -> 500 karena partial view-nya
                // belum ada (sudah dibuatkan filenya, lihat empty-state.blade.php).
                if ($request->status === 'today') {
                    $q->whereDate('rental_date', now('Asia/Jakarta')->toDateString());
                } else {
                    $q->where('rental_status', $request->status);
                }
            })
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

    public function create(Request $request)
    {
        $user = Auth::user();

        // FIX BUG BESAR: sebelumnya SEMUA query di bawah ini pakai
        // `$user->branch_id` mentah-mentah. Untuk super_admin, branch_id
        // BERNILAI NULL (memang tidak terikat 1 cabang tertentu) — di SQL,
        // `WHERE branch_id = NULL` TIDAK PERNAH cocok dengan baris manapun
        // (beda dengan `IS NULL`). Akibatnya form "Buat Penyewaan" untuk
        // super_admin selalu tampil KOSONG TOTAL (0 customer, 0 produk),
        // dan rental yang berhasil dibuat (kalau dipaksakan) akan tersimpan
        // dengan branch_id = NULL juga -> staf non-super-admin manapun
        // (termasuk sales) DITOLAK (403) saat mencoba memproses
        // pengembaliannya, karena branch_id mereka tidak pernah sama
        // dengan NULL.
        //
        // Sekarang: super_admin WAJIB memilih 1 cabang dulu (lewat
        // dropdown di halaman ini, submit via GET ?branch_id=X — pola yang
        // sama seperti sudah dipakai di ReportController) sebelum bisa
        // melihat/membuat transaksi. Staf biasa (non-super-admin) tidak
        // berubah sama sekali — otomatis pakai cabangnya sendiri seperti
        // sebelumnya.
        $branches = $user->isSuperAdmin() ? Branch::where('is_active', true)->orderBy('name')->get() : collect();

        $selectedBranchId = $user->isSuperAdmin()
            ? ($request->filled('branch_id') ? (int) $request->branch_id : null)
            : $user->branch_id;

        if ($user->isSuperAdmin() && $selectedBranchId) {
            abort_unless($branches->contains('id', $selectedBranchId), 404);
        }

        // Super_admin belum pilih cabang -> tampilkan halaman pilih cabang
        // saja (tidak query customer/produk sama sekali, supaya tidak
        // membingungkan dengan tampilan kosong seperti sebelumnya).
        if ($user->isSuperAdmin() && !$selectedBranchId) {
            return view('rentals.create', [
                'branches' => $branches, 'selectedBranchId' => null,
                'customers' => collect(), 'categories' => collect(),
                'products' => collect(), 'durationDays' => RentalService::DURATION_DAYS,
                'packages' => collect(), 'defaultPkg' => null,
            ]);
        }

        $customers = Customer::where('branch_id', $selectedBranchId)
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
                'id_photo'      => $c->is_id_photo_reusable ? asset('storage/' . $c->id_photo) : null,
                'id_photo_expired' => (bool) ($c->id_photo && !$c->is_id_photo_reusable),
                'notes'         => $c->notes,
                // Dipakai frontend untuk membedakan pesan "customer baru" (belum
                // pernah menyewa) vs "customer lama" yang kebetulan belum punya
                // foto jaminan tersimpan (mis. transaksi lamanya belum lengkap).
                'rental_count'  => $c->rentals_count,
            ]);

        $categories = Category::where('is_active', true)->withCount('products')->get();

        $products = Product::with('category')
            ->where('branch_id', $selectedBranchId)
            ->where('status', 'available')
            ->where('stock_available', '>', 0)
            ->orderBy('name')
            ->get();

        $durationDays = RentalService::DURATION_DAYS;

        $packages = RentalPackage::active()->get();
        $defaultPkg = $packages->firstWhere('duration_days', 3) ?? $packages->first();

        return view('rentals.create', compact('customers', 'categories', 'products', 'durationDays', 'packages', 'defaultPkg', 'branches', 'selectedBranchId'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

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
            // FIX BUG BESAR: super_admin WAJIB mengirim branch_id (dipilih
            // di halaman create -> diteruskan sebagai hidden input). Tanpa
            // ini, rental tersimpan dengan branch_id NULL -> staf mana pun
            // (termasuk sales) akan DITOLAK (403) saat memproses
            // pengembaliannya nanti, karena branch_id mereka tidak akan
            // pernah sama dengan NULL. Staf non-super-admin TIDAK perlu
            // mengirim field ini sama sekali (diabaikan, selalu pakai
            // cabangnya sendiri — lihat createRental()).
            'branch_id'             => [$user->isSuperAdmin() ? 'required' : 'nullable', 'exists:branches,id'],
        ], [
            'id_number.regex'  => 'Nomor KTP hanya boleh berisi angka.',
            'branch_id.required' => 'Pilih cabang tujuan transaksi ini terlebih dahulu.',
        ]);

        // ── Verifikasi KTP customer ──────────────────────────────────────────
        // Customer LAMA (sudah punya foto KTP tersimpan) boleh pakai foto lama.
        // Customer BARU (belum punya foto KTP di database) WAJIB upload foto KTP
        // + nomor KTP saat ini, saat transaksi penyewaan dibuat.
        //
        $customer = Customer::findOrFail($request->customer_id);

        $guaranteeType = $request->input('guarantee.type');
        $photoRequired = in_array($guaranteeType, ['ktp', 'sim'], true);

        if ($photoRequired && !$customer->is_id_photo_reusable && !$request->hasFile('id_photo')) {
            $message = $customer->id_photo
                ? 'Masa berlaku pakai-ulang foto jaminan customer ini sudah habis. Foto wajib diunggah ulang untuk transaksi ini.'
                : 'Customer ini belum memiliki foto jaminan tersimpan. Foto wajib diunggah untuk penyewaan pertama dengan jaminan ' . strtoupper($guaranteeType) . '.';

            return back()->withInput()->withErrors(['id_photo' => $message]);
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
                $customer->id_photo_type = $request->input('guarantee.type');
                $customer->id_photo_reusable_until = null;
            } elseif ($customer->id_photo && !$customer->id_photo_type && $request->filled('guarantee.type')) {
                $customer->id_photo_type = $request->input('guarantee.type');
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
            // FIX: 'numeric' saja masih meloloskan notasi seperti "1e5" atau
            // "1.5" — tambahkan regex integer-only supaya benar-benar hanya
            // angka bulat (selaras dengan input "numerals only" di frontend).
            'amount'           => ['required', 'numeric', 'min:1', 'regex:/^[0-9]+$/'],
            'method'           => 'required|in:cash,transfer,qris,other',
            // Bank/e-wallet: wajib untuk transfer & qris, tidak dipakai untuk cash.
            'payment_channel'  => 'nullable|required_if:method,transfer|required_if:method,qris|string|max:50',
            // Nomor rekening: wajib HANYA untuk transfer (QRIS tidak butuh nomor rekening).
            'account_number'   => 'nullable|required_if:method,transfer|string|max:50',
            // `notes` is a general-purpose field (formerly "reference_number").
            // Keep accepting `reference_number` for backward compatibility.
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',

            // Sub-form "Lainnya": kartu kredit/debit ATAU jaminan barang.
            'otherOptions'          => 'nullable|required_if:method,other|in:card,guarantee',
            'card_type'             => 'nullable|required_if:otherOptions,card|in:credit,debit',
            'card_bank'             => 'nullable|required_if:otherOptions,card|string|max:50',
            'card_reference'        => 'nullable|required_if:otherOptions,card|string|max:100',
            // Exclude all guarantee-related fields unless `otherOptions` == 'guarantee'.
            'guarantee_name'        => 'exclude_unless:otherOptions,guarantee|required|string|max:150',
            'guarantee_brand'       => 'exclude_unless:otherOptions,guarantee|nullable|string|max:100',
            'guarantee_condition'   => 'exclude_unless:otherOptions,guarantee|nullable|string|max:50',
            'guarantee_value'       => 'exclude_unless:otherOptions,guarantee|nullable|numeric|min:0',
            'guarantee_serial'      => 'exclude_unless:otherOptions,guarantee|nullable|string|max:100',
            'guarantee_note'        => 'exclude_unless:otherOptions,guarantee|nullable|string|max:1000',
            // Only validate guarantee files when paying with `other` + `guarantee` option.
            'guarantee_photos'      => 'exclude_unless:otherOptions,guarantee|array|max:5',
            'guarantee_photos.*'    => 'exclude_unless:otherOptions,guarantee|image|max:4096',
        ], [
            'amount.regex' => 'Jumlah bayar harus berupa angka saja (tanpa huruf, titik, atau koma).',
        ]);

        $data = $request->all();

        // Backwards compatibility: if frontend sends `reference_number` but not
        // `notes`, treat it as a short note for the rental/payment (customer
        // pickup info, delivery note, etc.). This repurposes the old
        // "No. Referensi" input into a free-text `notes` field.
        if (empty($data['notes']) && !empty($data['reference_number'])) {
            $data['notes'] = $data['reference_number'];
        }

        // Rakit detail sub-form "Lainnya" jadi satu struktur JSON — sebelumnya
        // field-field ini dikumpulkan form tapi tidak pernah tersimpan sama
        // sekali ke database (hilang begitu submit).
        if ($request->input('method') === 'other') {
            $data['other_type'] = $request->input('otherOptions');

            if ($data['other_type'] === 'card') {
                $data['other_payment_details'] = [
                    'card_type' => $request->input('card_type'),
                    'card_bank' => $request->input('card_bank'),
                    'card_reference' => $request->input('card_reference'),
                ];
            } elseif ($data['other_type'] === 'guarantee') {
                $photoPaths = [];
                if ($request->hasFile('guarantee_photos')) {
                    foreach ($request->file('guarantee_photos') as $photo) {
                        $photoPaths[] = $photo->store('payments/guarantee-items', 'public');
                    }
                }

                $data['other_payment_details'] = [
                    'guarantee_name'      => $request->input('guarantee_name'),
                    'guarantee_brand'     => $request->input('guarantee_brand'),
                    'guarantee_condition' => $request->input('guarantee_condition'),
                    'guarantee_value'     => $request->input('guarantee_value'),
                    'guarantee_serial'    => $request->input('guarantee_serial'),
                    'guarantee_note'      => $request->input('guarantee_note'),
                    'guarantee_photos'    => $photoPaths,
                ];
            }
        }

        $payment = $this->rentalService->processPayment($rental, $data);

        // If the user provided a note, persist it to the Rental so it shows
        // immediately on the rental detail page as the customer's request.
        if (!empty($data['notes'])) {
            // Append to existing rental notes if present, avoid overwriting useful info.
            $existing = (string) $rental->notes;
            $newNotes = trim($existing ? ($existing . "\n" . $data['notes']) : $data['notes']);
            $rental->update(['notes' => $newNotes]);

            // Also save this note as a customer preference so CS can reuse it
            // for future orders. Do not duplicate identical notes.
            $customer = $rental->customer()->first();
            if ($customer) {
                $custNotes = (string) $customer->notes;
                if ($custNotes === '' || !str_contains($custNotes, $data['notes'])) {
                    $append = $custNotes ? ($custNotes . "\n" . $data['notes']) : $data['notes'];
                    $customer->update(['notes' => $append]);
                }
            }
        }

        return back()->with('success', "Pembayaran {$payment->payment_number} berhasil dicatat!");
    }

    /**
     * FITUR BARU: Tentukan nominal denda keterlambatan (bisa 0 kalau memang
     * tidak ada denda) SEBELUM barang bisa diproses pengembalian fisiknya.
     * Ini langkah 1 dari 2 pada alur retur untuk rental yang overdue.
     */
    public function setLateFee(Request $request, Rental $rental)
    {
        $this->authorize('update', $rental);

        if ($rental->rental_status !== Rental::STATUS_OVERDUE) {
            return back()->with('error', 'Denda hanya bisa ditentukan untuk penyewaan yang berstatus terlambat.');
        }

        // Sudah dikonfirmasi & lunas sebelumnya -> tidak boleh diubah lagi
        // lewat sini (cegah staf "reset" denda yang pembayarannya sudah masuk).
        if (!is_null($rental->late_fee_confirmed_at) && $rental->payment_status === Rental::PAYMENT_PAID) {
            return back()->with('error', 'Denda untuk transaksi ini sudah ditentukan dan lunas.');
        }

        $data = $request->validate([
            // FIX: sama seperti 'amount' — integer only.
            'late_fee'      => ['required', 'numeric', 'min:0', 'regex:/^[0-9]+$/'],
            'late_fee_note' => 'nullable|string|max:255',
        ], [
            'late_fee.regex' => 'Nominal denda harus berupa angka saja (tanpa huruf, titik, atau koma).',
        ]);

        $lateFee  = (float) $data['late_fee'];
        $newTotal = max(0, (float) $rental->subtotal - (float) $rental->discount + $lateFee);

        $rental->update([
            'late_fee'              => $lateFee,
            'late_fee_note'         => $data['late_fee_note'] ?? null,
            'late_fee_confirmed_at' => now('Asia/Jakarta'),
            'overdue_days'          => $rental->live_late_days,
            'total_amount'          => $newTotal,
            // Kalau nominal denda kosong (0) DAN sebelumnya sudah lunas
            // sewa awal, langsung dianggap lunas — tidak perlu bayar apa-apa
            // lagi supaya rental dengan denda Rp 0 tidak ikut terblokir.
            'payment_status'        => $rental->paid_amount >= $newTotal ? Rental::PAYMENT_PAID : Rental::PAYMENT_PARTIAL,
        ]);

        if (class_exists(\App\Models\ActivityLog::class)) {
            \App\Models\ActivityLog::record(
                'set_late_fee',
                "Menentukan denda keterlambatan {$rental->invoice_number} sebesar Rp " . number_format($lateFee, 0, ',', '.'),
                $rental
            );
        }

        if ($lateFee <= 0) {
            return redirect()->route('rentals.scan.show', $rental->invoice_number)
                ->with('success', 'Tidak ada denda keterlambatan. Silakan lanjutkan proses pengembalian barang.');
        }

        return redirect()->route('rentals.scan.show', $rental->invoice_number)
            ->with('success', 'Denda sebesar Rp ' . number_format($lateFee, 0, ',', '.') . ' ditentukan. Pengembalian barang baru bisa diproses setelah denda dibayar lunas.');
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

        // FIX (aturan baru): kalau rental terlambat, denda WAJIB ditentukan
        // dan DIBAYAR LUNAS terlebih dahulu sebelum barang bisa ditandai
        // dikembalikan. Guard ini dipasang di backend (bukan cuma
        // disembunyikan di UI) supaya tidak bisa dilewati dengan POST
        // langsung ke endpoint ini.
        if (!$rental->can_be_returned) {
            $message = match (true) {
                $rental->needs_late_fee_confirmation => 'Tentukan dulu nominal denda keterlambatan sebelum barang bisa dikembalikan.',
                $rental->needs_late_fee_payment       => 'Denda keterlambatan harus dibayar lunas terlebih dahulu sebelum barang dapat dikembalikan.',
                // FITUR BARU: assessment sudah pernah dilakukan sebelumnya
                // (misal ada barang rusak/hilang dengan tagihan tunai) tapi
                // belum lunas -> jangan proses ulang form ini, arahkan ke
                // pelunasan.
                $rental->needs_return_payment         => 'Kondisi barang sudah pernah dicatat & masih ada tagihan yang belum lunas (Rp ' . number_format($rental->remaining_amount, 0, ',', '.') . '). Lunasi dulu sebelum pengembalian bisa diselesaikan.',
                default                                => 'Penyewaan ini belum bisa diproses pengembalian.',
            };

            return redirect()
                ->route('rentals.scan.show', $rental->invoice_number)
                ->with('error', $message);
        }

        // ── Validasi sesuai struktur form di show.blade.php & scan-result.blade.php ───────────
        $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.rental_item_id'    => 'required|exists:rental_items,id',
            'items.*.condition'         => 'required|in:good,damaged,lost',
            'items.*.notes'             => 'nullable|string|max:1000',
            'items.*.penalty_resolution' => 'nullable|required_if:items.*.condition,damaged|required_if:items.*.condition,lost|in:charge_double,claim_guarantee',
            'items.*.penalty_amount'    => 'nullable|required_if:items.*.penalty_resolution,charge_double|numeric|min:0',
            'items.*.guarantee_id'      => 'nullable|required_if:items.*.penalty_resolution,claim_guarantee|exists:guarantees,id',
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

        // FITUR BARU ("tidak bisa dikembalikan sampai lunas"): kalau setelah
        // dinilai kondisinya ternyata masih ada kekurangan bayar (misal ada
        // barang rusak/hilang yang dibebankan tunai), RentalService::processReturn()
        // TIDAK memfinalisasi retur ini — barang belum ditandai "dikembalikan"
        // (is_returned masih false, rental_status belum maju ke laundry).
        // Staf WAJIB melunasi dulu (lihat needs_return_payment di model Rental)
        // sebelum transaksi ini benar-benar dianggap selesai.
        if ((float) $rental->remaining_amount > 0) {
            $formatted = 'Rp ' . number_format($rental->remaining_amount, 0, ',', '.');
            return redirect()
                ->route('rentals.show', $rental)
                ->with('require_payment', true)
                // FIX BUG (penyebab 500 error): kolom payments.type di database
                // adalah ENUM ['rental','deposit','late_fee','damage_fee','refund'] —
                // TIDAK ada nilai 'damage'. Memakai 'damage' membuat query INSERT
                // payment gagal (SQLSTATE 1265 Data truncated) begitu staf mencoba
                // membayar kekurangan lewat modal ini -> berujung 500.
                ->with('payment_type', 'damage_fee')
                ->with('error', "Kondisi barang sudah dicatat, TAPI belum dianggap dikembalikan. Sisa tagihan {$formatted} harus dibayar lunas dulu — begitu lunas, sistem otomatis menyelesaikan pengembalian ini.");
        }

        return redirect()
            ->route('rentals.scan.show', $rental->invoice_number)
            ->with('success', "Pengembalian {$rental->invoice_number} berhasil diproses!");
    }

    /**
     * Fallback/jaga-jaga manual: finalisasi retur untuk rental yang sudah
     * dinilai (return_assessed_at terisi) & sudah lunas, tapi karena suatu
     * sebab belum sempat difinalisasi otomatis oleh processPayment().
     * Pada alur normal endpoint ini jarang terpakai.
     */
    public function finalizeReturn(Rental $rental)
    {
        $this->authorize('update', $rental);

        if (!$rental->can_finalize_return) {
            return back()->with('error', 'Transaksi ini belum bisa difinalisasi — pastikan tagihan sudah lunas.');
        }

        $rental = $this->rentalService->finalizeReturn($rental);

        return redirect()
            ->route('rentals.scan.show', $rental->invoice_number)
            ->with('success', "Pengembalian {$rental->invoice_number} berhasil diselesaikan!");
    }

        public function updateDiscount(Request $request, Rental $rental)
    {
        $this->authorize('update', $rental);

        $user = Auth::user();
        if (!in_array($user->role, ['admin_toko', 'super_admin', ])) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah diskon.');
        }

        if (in_array($rental->rental_status, ['returned', 'cancelled'])) {
            return back()->with('error', 'Diskon tidak dapat diubah untuk transaksi yang sudah selesai/dibatalkan.');
        }

        $data = $request->validate([
            'discount_amount'  => 'nullable|numeric|min:0',
            'discount_reason'  => 'nullable|string|max:500',
        ]);

        $subtotal = (float) $rental->subtotal;

        // Only nominal discounts supported: use the provided amount (or 0).
        $discount = (float) ($data['discount_amount'] ?? 0);

        // Diskon tidak boleh melebihi subtotal
        $discount = min($discount, $subtotal);

        $oldDiscount = (float) $rental->discount;

        // total_amount = subtotal - discount + late_fee, konsisten dengan nota/invoice/PDF/thermal
        // yang semuanya membaca dari kolom total_amount hasil hitungan ini.
        $rental->update([
            'discount'            => $discount,
            'total_amount'        => max(0, $subtotal - $discount + (float) $rental->late_fee),
            'discount_type'       => 'nominal',
            'discount_value'      => $discount,
            'discount_description'=> $data['discount_reason'] ?? null,
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

        if (in_array($rental->rental_status, ['returned', 'cancelled', 'menunggu_laundry', 'dalam_laundry', 'siap_disewakan'])) {
            return back()->with('error', 'Penyewaan ini tidak dapat dibatalkan karena barang sudah/sedang diproses pengembalian.');
        }

        // Sewa yang sudah AKTIF/OVERDUE (sudah lunas & barang di tangan customer)
        // TIDAK LAGI bisa dibatalkan — satu-satunya jalan keluar adalah proses
        // retur normal ("Proses Pengembalian"). Dicek di server, bukan cuma
        // disembunyikan tombolnya, supaya tidak bisa dilewati lewat request langsung.
        if (in_array($rental->rental_status, ['active', 'overdue'])) {
            return back()->with('error', 'Penyewaan yang sudah aktif (sudah dibayar & barang di tangan customer) tidak dapat dibatalkan. Gunakan menu "Proses Pengembalian" untuk mengakhiri sewa ini.');
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($rental, $request) {
            foreach ($rental->items as $item) {
                if ($item->product_id) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->increment('stock_available', $item->quantity);
                        $product->refresh();
                        if ($product->stock_available > 0 && $product->status === 'rented') {
                            $product->update(['status' => 'available']);
                        }
                    }
                }
            }

            $rental->update([
                'rental_status'      => 'cancelled',
                'cancel_reason'      => $request->reason,
                'cancelled_at'       => now(),
                'cancelled_by'       => Auth::id(),
                'cancel_laundry_fee' => 0,
                'total_amount'       => 0,
                // FIX: sebelumnya payment_status tidak disentuh sama sekali, jadi
                // kalau kebetulan sebelumnya 'paid'/'partial', badge akan tetap
                // menampilkan itu berdampingan dengan "DIBATALKAN" (membingungkan,
                // seolah tagihan Rp 0 sudah "lunas" padahal customer belum pernah
                // bayar). Dipaksa 'unpaid' karena rental hanya bisa sampai sini
                // kalau statusnya masih 'waiting' (belum pernah lunas penuh).
                'payment_status'     => 'unpaid',
            ]);

            // ── Aturan reuse KTP setelah pembatalan sebelum aktif ───────────────
            // Customer masih punya sewa AKTIF LAIN (KTP fisik masih tertahan di
            // toko) -> foto KTP tersimpan boleh dipakai ulang, tapi cuma 30 menit.
            // Tidak ada sewa aktif lain sama sekali -> reuse langsung dimatikan,
            // wajib scan/upload ulang di transaksi berikutnya.
            //
            // "Aktif" di sini TIDAK sama persis dengan rental_status='active':
            //  - status 'active'                              -> selalu dihitung aktif.
            //  - status 'overdue' TAPI denda belum dibayar     -> GREY ZONE, dianggap
            //    TIDAK aktif (barang belum tentu akan diambil lagi, KTP tidak
            //    otomatis dianggap "aman ditahan").
            //  - status 'overdue' DAN denda sudah lunas        -> dianggap aktif
            //    (barang masih di tangan customer / belum diretur, tapi urusan
            //    uangnya sudah beres, jadi KTP tetap sah ditahan sebagai jaminan).
            $customer = $rental->customer;
            if ($customer) {
                $hasOtherActiveRental = $customer->rentals()
                    ->where('id', '!=', $rental->id)
                    ->where(function ($q) {
                        $q->where('rental_status', 'active')
                          ->orWhere(function ($q2) {
                              $q2->where('rental_status', 'overdue')
                                 ->where('payment_status', 'paid');
                          });
                    })
                    ->exists();

                if ($hasOtherActiveRental) {
                    $customer->update([
                        'id_photo_reusable_until' => now()->addMinutes(30),
                    ]);
                } else {
                    if ($customer->id_photo) {
                        try {
                            Storage::disk('public')->delete($customer->id_photo);
                        } catch (\Throwable $e) {
                        }
                    }
                    $customer->update([
                        'id_photo'                => null,
                        'id_photo_type'           => null,
                        'id_photo_reusable_until' => null,
                    ]);
                }
            }
        });

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

        $rental->load(['customer', 'items.product', 'guarantees', 'branch', 'createdBy', 'returnedBy', 'package']);
        $qrCode = base64_encode(QrCode::format('svg')->size(100)->generate(route('rentals.show', $rental->id)));
        return view('rentals.invoice', compact('rental', 'qrCode'));
    }

    public function thermalPrint(Rental $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['customer', 'items.product', 'branch', 'package', 'createdBy', 'returnedBy']);
        $qrCode = base64_encode(QrCode::format('svg')->size(80)->generate(route('rentals.show', $rental->id)));
        return view('rentals.thermal', compact('rental', 'qrCode'));
    }

    public function exportPdf(Rental $rental)
    {
        $this->authorize('view', $rental);

        $rental->load(['customer', 'items.product', 'guarantees', 'branch', 'createdBy', 'returnedBy', 'package']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rentals.pdf', compact('rental'));
        return $pdf->download("invoice-{$rental->invoice_number}.pdf");
    }

    public function invoicePublic(string $token)
    {
        $rental = Rental::where('public_token', $token)
            ->with(['customer', 'items.product', 'guarantees', 'branch', 'createdBy', 'returnedBy'])
            ->firstOrFail();
        return view('rentals.invoice-public', compact('rental'));
    }

        public function invoicePdfPublic(string $token)
    {
        $rental = Rental::where('public_token', $token)
            ->with(['customer', 'items.product', 'guarantees', 'branch', 'createdBy', 'returnedBy', 'package'])
            ->firstOrFail();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rentals.pdf', compact('rental'));
        return $pdf->stream("invoice-{$rental->invoice_number}.pdf");
    }

    public function qrisDemo(Rental $rental)
    {
        return view('rentals.qris-demo', compact('rental'));
    }

    public function qrisDemoQr(Rental $rental)
    {
        $url = route('rentals.qris-demo', $rental);
        $svg = QrCode::format('svg')->size(220)->margin(1)->generate($url);

        return response($svg, 200)->header('Content-Type', 'image/svg+xml');
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
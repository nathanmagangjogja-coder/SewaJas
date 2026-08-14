<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalPackage;
use App\Models\RentalReturn;
use App\Models\Product;
use App\Models\Laundry;
use App\Models\Payment;
use App\Models\Guarantee;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class RentalService
{
    // ─── Fallback jika paket tidak ditemukan ──────────────────────────────────
    const DURATION_DAYS      = 3;
    const DEFAULT_PENALTY_PC = 10.0;  // 10% per hari

    const CONDITION_PRIORITY = [
        'good'    => 1,
        'damaged' => 2,
        'lost'    => 3,
    ];

    // ─── Invoice & Payment Number ─────────────────────────────────────────────

    public function generateInvoiceNumber(int $branchId): string
    {
        $prefix     = 'INV';
        $date       = now('Asia/Jakarta')->format('Ymd');
        $branchCode = str_pad($branchId, 2, '0', STR_PAD_LEFT);

        $last = Rental::where('invoice_number', 'like', "{$prefix}{$date}{$branchCode}%")
            ->orderBy('id', 'desc')->first();

        $sequence = $last ? (int) substr($last->invoice_number, -4) + 1 : 1;

        return $prefix . $date . $branchCode . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $date   = now('Asia/Jakarta')->format('Ymd');
        $last   = Payment::where('payment_number', 'like', "{$prefix}{$date}%")
            ->orderBy('id', 'desc')->first();
        $sequence = $last ? (int) substr($last->payment_number, -4) + 1 : 1;
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // ─── Create Rental ────────────────────────────────────────────────────────

    public function createRental(array $data): Rental
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();

            // ── Resolve paket ─────────────────────────────────────────────────
            $package      = null;
            $durationDays = self::DURATION_DAYS;

            if (!empty($data['package_id'])) {
                $package = RentalPackage::find($data['package_id']);
            }

            if ($package) {
                // Paket Custom: admin isi durasi manual
                $durationDays = $package->is_custom
                    ? (int) ($data['custom_duration_days'] ?? 3)
                    : $package->duration_days;
            } elseif (!empty($data['duration_days'])) {
                $durationDays = (int) $data['duration_days'];
            }

            // ── Hitung subtotal ───────────────────────────────────────────────
            $discount = (float) ($data['discount'] ?? 0);
            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $product  = Product::findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];
                $subtotal += $product->rental_price * $quantity;
            }

            $totalAmount = max(0, $subtotal - $discount);

            // FIX BUG BESAR ("sales tidak bisa proses retur rental yang
            // dibuat super_admin"): sebelumnya branch_id rental SELALU
            // diambil dari $user->branch_id mentah-mentah. Untuk
            // super_admin, branch_id itu NULL (memang tidak terikat 1
            // cabang) -> rental tersimpan dengan branch_id NULL -> staf
            // manapun (termasuk sales) DITOLAK (403) saat proses
            // pengembaliannya nanti, karena branch_id mereka tidak akan
            // pernah sama dengan NULL. generateInvoiceNumber() juga
            // mensyaratkan `int` (bukan nullable) -> kalau sampai lolos ke
            // sini, null akan melempar TypeError (500).
            //
            // Sekarang: kalau super_admin, WAJIB pakai branch_id yang
            // dipilih eksplisit di form (dikirim lewat $data['branch_id'],
            // sudah divalidasi 'required' di controller). Staf biasa
            // (non-super-admin) TIDAK berubah sama sekali — tetap otomatis
            // pakai cabangnya sendiri seperti sebelumnya.
            $resolvedBranchId = $user->isSuperAdmin()
                ? (int) $data['branch_id']
                : $user->branch_id;

            // ── Buat rental ───────────────────────────────────────────────────
            $rental = Rental::create([
                'invoice_number'  => $this->generateInvoiceNumber($resolvedBranchId),
                'branch_id'       => $resolvedBranchId,
                'customer_id'     => $data['customer_id'],
                'created_by'      => $user->id,
                'package_id'      => $package?->id,
                'rental_date'     => $data['rental_date'],
                'return_due_date' => Carbon::parse($data['rental_date'], 'Asia/Jakarta')
                                        ->addDays($durationDays),
                'duration_days'   => $durationDays,
                'subtotal'        => $subtotal,
                'discount'        => $discount,
                'total_amount'    => $totalAmount,
                'paid_amount'     => 0,
                'payment_status'  => Rental::PAYMENT_UNPAID,
                'rental_status'   => Rental::STATUS_WAITING,
                'notes'           => $data['notes'] ?? null,
            ]);

            // ── Buat rental items ─────────────────────────────────────────────
            foreach ($data['items'] as $item) {
                $product      = Product::findOrFail($item['product_id']);
                $quantity     = (int) $item['quantity'];
                $itemSubtotal = $product->rental_price * $quantity;

                RentalItem::create([
                    'rental_id'              => $rental->id,
                    'product_id'             => $product->id,
                    'product_name'           => $product->name,
                    'product_size'           => $product->size,
                    'product_color'          => $product->color,
                    'quantity'               => $quantity,
                    'price_per_day'          => $product->rental_price,
                    'duration_days'          => $durationDays,
                    'package_duration_days'  => $durationDays,
                    'subtotal'               => $itemSubtotal,
                ]);

                $product->decrement('stock_available', $quantity);
                $product->refresh();
                if ($product->stock_available <= 0) {
                    $product->update(['status' => 'rented']);
                }
            }

            // ── Jaminan ───────────────────────────────────────────────────────
            if (!empty($data['guarantee'])) {
                Guarantee::create([
                    'rental_id'      => $rental->id,
                    'type'           => $data['guarantee']['type'],
                    'id_number'      => $data['guarantee']['id_number'] ?? null,
                    'id_name'        => $data['guarantee']['id_name'] ?? null,
                    'deposit_amount' => (float) ($data['guarantee']['deposit_amount'] ?? 0),
                    'description'    => $data['guarantee']['description'] ?? null,
                    'status'         => 'held',
                ]);
            }

            $this->generateQrCode($rental);
            $this->logActivity('create_rental', $rental,
                "Membuat penyewaan {$rental->invoice_number}" .
                ($package ? " — {$package->name}" : '')
            );

            return $rental->fresh(['items', 'customer', 'guarantees', 'package']);
        });
    }

    // ─── Process Payment ──────────────────────────────────────────────────────

    public function processPayment(Rental $rental, array $data): Payment
    {
        return DB::transaction(function () use ($rental, $data) {
            $payment = Payment::create([
                'rental_id'        => $rental->id,
                'received_by'      => Auth::id(),
                'payment_number'   => $this->generatePaymentNumber(),
                'amount'           => (float) $data['amount'],
                'method'           => $data['method'],
                'payment_channel'  => $data['payment_channel'] ?? null,
                'account_number'   => $data['account_number'] ?? null,
                'other_type'             => $data['other_type'] ?? null,
                'other_payment_details'  => $data['other_payment_details'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'type'             => $data['type'] ?? 'rental',
                'notes'            => $data['notes'] ?? null,
                'paid_at'          => now('Asia/Jakarta'),
            ]);

            $newPaidAmount = $rental->paid_amount + (float) $data['amount'];
            $isFullyPaid   = $newPaidAmount >= $rental->total_amount;

            // FIX: rental_status hanya boleh otomatis maju ke ACTIVE dari status
            // WAITING (pelunasan invoice awal -> mengaktifkan sewa). Sebelumnya
            // kondisi ini juga mencakup ACTIVE & OVERDUE, sehingga melunasi
            // DENDA (telat / rusak-hilang) pada rental yang overdue keliru
            // mengembalikan status jadi 'active' — padahal fisik barangnya
            // masih belum dikembalikan dan tanggal jatuh temponya tetap sudah
            // lewat. Overdue ditentukan oleh TANGGAL (lihat UpdateOverdueRentals),
            // bukan oleh status pembayaran, jadi tidak boleh "dinaikkan" hanya
            // karena tagihan lunas.
            $nextStatus = ($isFullyPaid && $rental->rental_status === Rental::STATUS_WAITING)
                ? Rental::STATUS_ACTIVE
                : $rental->rental_status;

            $rental->update([
                'paid_amount'    => $newPaidAmount,
                'payment_status' => $isFullyPaid ? 'paid' : 'partial',
                'rental_status'  => $nextStatus,
            ]);

            $this->logActivity('process_payment', $rental,
                "Pembayaran {$payment->payment_number} sebesar Rp " .
                number_format($data['amount'], 0, ',', '.')
            );

            // FITUR BARU ("tidak bisa dikembalikan sampai lunas"): kalau rental
            // ini sebelumnya sudah melalui fase assessment kondisi barang
            // (return_assessed_at terisi) tapi masih ada kekurangan bayar, dan
            // pembayaran BARU SAJA ini membuatnya lunas -> langsung finalisasi
            // pengembalian di sini juga (tanpa staf perlu klik tombol lagi).
            $rental->refresh();
            if (
                !is_null($rental->return_assessed_at)
                && $rental->payment_status === 'paid'
                && in_array($rental->rental_status, [Rental::STATUS_ACTIVE, Rental::STATUS_OVERDUE], true)
            ) {
                $this->finalizeReturn($rental);
            }

            return $payment;
        });
    }

    // ─── Process Return ───────────────────────────────────────────────────────

    /**
     * FASE 1: ASSESSMENT kondisi barang.
     *
     * FITUR BARU ("tidak bisa dikembalikan sampai lunas"): method ini TIDAK
     * langsung menandai barang sebagai "sudah dikembalikan". Ia hanya
     * mencatat kondisi tiap barang, menghitung denda rusak/hilang (kalau
     * ada), dan memperbarui tagihan (`total_amount`/`payment_status`).
     *
     * - Kalau setelah dihitung TIDAK ada kekurangan bayar (semua kondisi
     *   baik, atau kerusakan ditutup jaminan) -> langsung difinalisasi di
     *   detik yang sama lewat finalizeReturn() (perilaku terasa sama seperti
     *   sebelumnya, tidak ada langkah tambahan buat kasus normal).
     * - Kalau MASIH ada kekurangan bayar (mis. staf pilih "charge_double" /
     *   bayar tunai atas kerusakan, dan uangnya belum diserahkan) -> barang
     *   TETAP berstatus "belum dikembalikan" (is_returned tetap false,
     *   rental_status tidak maju, tidak masuk antrean laundry) sampai
     *   kekurangan itu dibayar lunas (lihat processPayment() yang akan
     *   otomatis memanggil finalizeReturn() begitu lunas).
     */
    public function processReturn(Rental $rental, array $data = []): Rental
    {
        return DB::transaction(function () use ($rental, $data) {
            $now     = now('Asia/Jakarta');
            $today   = $now->copy()->startOfDay();
            $dueDate = Carbon::parse($rental->return_due_date, 'Asia/Jakarta')->startOfDay();

            // ── Keterlambatan (informasi hari saja) & denda ────────────────────
            // FIX (aturan baru "denda dibayar dulu"): nominal denda TIDAK lagi
            // diinput bersamaan dengan form pengembalian fisik. Sekarang denda
            // sudah ditentukan & WAJIB lunas terlebih dahulu lewat endpoint
            // terpisah RentalController::setLateFee() — nilainya sudah
            // tersimpan di $rental->late_fee. Di sini kita hanya membacanya
            // sebagai sumber kebenaran tunggal, supaya tidak dobel hitung
            // dengan $rental->total_amount yang sudah memasukkan late_fee itu.
            $overdueDays = 0;
            if ($today->gt($dueDate)) {
                $overdueDays = (int) $dueDate->diffInDays($today);
            }
            $lateFee = (float) $rental->late_fee;

            // ── Diskon Manual (BARU) ──────────────────────────────────────────
            // Opsional: hanya dihitung/disimpan kalau dikirim dari form retur
            // (scan-result.blade.php). Kalau tidak dikirim, perilaku LAMA
            // tetap 100% tidak berubah (lihat percabangan $hasManualDiscount
            // di bawah, dipakai saat menghitung $totalWithFees).
            $discountType        = $data['discount_type'] ?? null;
            $discountValueRaw    = isset($data['discount_value']) && $data['discount_value'] !== ''
                ? (float) $data['discount_value']
                : null;
            $discountName        = $data['discount_name'] ?? null;
            $discountDescription = $data['discount_description'] ?? null;

            $hasManualDiscount = in_array($discountType, ['nominal', 'percent'], true)
                && $discountValueRaw !== null
                && $discountValueRaw > 0;

            $subtotal = (float) $rental->subtotal;
            $discountAmount = null;

            if ($hasManualDiscount) {
                $base = $subtotal + $lateFee;

                $discountAmount = $discountType === 'percent'
                    ? round($base * ($discountValueRaw / 100), 2)
                    : $discountValueRaw;

                // Diskon tidak boleh melebihi (subtotal + denda) -> cegah total negatif
                $discountAmount = min($discountAmount, $base);
            }

            // ── Kondisi terburuk dari semua item ─────────────────────────────
            $priority       = self::CONDITION_PRIORITY;
            $worstCondition = 'good';
            $worstNotes     = null;
            $items          = $data['items'] ?? [];

            // ── Denda Barang Rusak/Hilang (BARU) ──────────────────────────────
            // Per-item, kalau kondisi damaged/lost, petugas pilih salah satu:
            //   - charge_double : damage_fee item = subtotal item x 2, masuk ke total tagihan
            //   - claim_guarantee : item ini "ditutup" oleh jaminan (KTP/deposit) yang disita,
            //     tidak menambah tagihan uang. Jaminan disita SEKALI untuk seluruh rental
            //     (bukan per-item) — lihat proses forfeit setelah loop ini.
            $totalDamageFee     = 0;
            $guaranteeClaimed   = false;
            $claimedItemNames   = [];
            $guaranteesToForfeit = [];

            foreach ($items as $itemData) {
                $rentalItem = RentalItem::find($itemData['rental_item_id']);
                if (!$rentalItem) continue;

                $condition = $itemData['condition'] ?? 'good';
                $notes     = $itemData['notes'] ?? null;

                $penaltyResolution = $itemData['penalty_resolution'] ?? null;
                $itemDamageFee     = 0;

                if (in_array($condition, ['damaged', 'lost'], true)) {
                    if ($penaltyResolution === 'charge_double') {
                        // If frontend provided an explicit penalty_amount, use it;
                        // otherwise fallback to default rule (double item subtotal).
                        if (isset($itemData['penalty_amount']) && $itemData['penalty_amount'] !== '') {
                            $itemDamageFee = max(0, (float) $itemData['penalty_amount']);
                        } else {
                            $itemDamageFee = (float) $rentalItem->subtotal * 2;
                        }
                        $totalDamageFee += $itemDamageFee;
                    } elseif ($penaltyResolution === 'claim_guarantee') {
                        $guaranteeClaimed = true;
                        $claimedItemNames[] = $rentalItem->product_name;

                        if (!empty($itemData['guarantee_id'])) {
                            $guaranteesToForfeit[] = $itemData['guarantee_id'];
                        }
                    }
                }

                // FIX (aturan baru "tidak bisa dikembalikan sampai lunas"):
                // hanya catat HASIL PENILAIAN kondisi barang di sini.
                // is_returned SENGAJA belum di-set true, dan belum ada entri
                // Laundry — itu baru terjadi di finalizeReturn(), yang hanya
                // dipanggil kalau tagihan sudah lunas (lihat bawah).
                $rentalItem->update([
                    'return_condition' => $condition,
                    'return_notes'     => $notes,
                    'damage_fee'       => $itemDamageFee,
                ]);

                if (($priority[$condition] ?? 0) > ($priority[$worstCondition] ?? 0)) {
                    $worstCondition = $condition;
                    $worstNotes     = $notes;
                }
            }

            // Fallback jika items kosong -> anggap semua kondisi baik
            if (empty($items)) {
                foreach ($rental->items as $item) {
                    $item->update(['return_condition' => 'good']);
                }
            }

            // Jaminan yang dipilih "claim_guarantee" disita SEKARANG juga —
            // ini bukan kekurangan tunai yang perlu ditagih, jadi tidak perlu
            // menunggu fase pembayaran.
            if ($guaranteeClaimed) {
                if (!empty($guaranteesToForfeit)) {
                    foreach (array_unique($guaranteesToForfeit) as $gid) {
                        $g = $rental->guarantees()->where('id', $gid)->where('status', 'held')->first();
                        if ($g) {
                            $g->update([
                                'status'      => 'forfeited',
                                'returned_at' => $now,
                                'notes'       => trim(($g->notes ? $g->notes . "\n" : '') . 'Disita otomatis akibat barang rusak/hilang: ' . implode(', ', $claimedItemNames)),
                            ]);
                        }
                    }
                } else {
                    $guaranteeToForfeit = $rental->guarantees()->where('status', 'held')->first();
                    if ($guaranteeToForfeit) {
                        $guaranteeToForfeit->update([
                            'status'      => 'forfeited',
                            'returned_at' => $now,
                            'notes'       => trim(
                                ($guaranteeToForfeit->notes ? $guaranteeToForfeit->notes . "\n" : '') .
                                'Disita otomatis akibat barang rusak/hilang: ' . implode(', ', $claimedItemNames)
                            ),
                        ]);
                    }
                }
            }

            // ── Hitung ulang total tagihan ──────────────────────────────────────
            if ($hasManualDiscount) {
                // Formula BARU (spec): total = subtotal + late_fee - discount, never negative.
                $totalWithFees = max(0, $subtotal + $lateFee - $discountAmount);
            } else {
                // FIX (aturan baru "denda dibayar dulu"): $rental->total_amount
                // di titik ini SUDAH mengikutsertakan $lateFee (diset & dilunasi
                // lewat setLateFee() sebelum form retur ini bisa diakses sama
                // sekali). Menambah $lateFee lagi di sini akan menghitungnya
                // dua kali. Perilaku LAMA (sebelum fitur ini ada) adalah
                // `$rental->total_amount + $lateFee` — sengaja TIDAK dipakai lagi.
                $totalWithFees = (float) $rental->total_amount;
            }

            $totalWithFees += $totalDamageFee;

            $updatePayload = [
                'late_fee'            => $lateFee,
                // FIX: late_fee_note sudah diisi (kalau ada) lewat setLateFee()
                // sebelum form retur ini bisa diakses. Jangan ditimpa null di
                // sini kecuali form retur benar-benar mengirim catatan baru.
                'late_fee_note'       => $data['late_fee_note'] ?? $rental->late_fee_note,
                'overdue_days'        => $overdueDays,
                'total_amount'        => $totalWithFees,
                // FIX: dihitung ulang murni dari perbandingan total vs. paid_amount,
                // supaya kalau ternyata ada kekurangan (denda rusak/hilang tunai),
                // status benar-benar berubah jadi 'partial' -> tombol "Bayar
                // Kekurangan" muncul & barang TIDAK dianggap selesai dikembalikan.
                'payment_status'      => $totalWithFees <= $rental->paid_amount ? 'paid' : 'partial',
                // Tandai: kondisi barang sudah dinilai. Dipakai needs_return_payment
                // / can_finalize_return / can_be_returned di model Rental.
                'return_assessed_at'  => $now,
            ];

            if ($hasManualDiscount) {
                $updatePayload['discount']             = $discountAmount;
                $updatePayload['discount_name']         = $discountName;
                $updatePayload['discount_description']  = $discountDescription;
                $updatePayload['discount_type']         = $discountType;
                $updatePayload['discount_value']        = $discountValueRaw;
            }

            $rental->update($updatePayload);
            $rental->refresh();

            $packageName = $rental->package?->name ?? 'tanpa paket';
            $dendaInfo   = $overdueDays > 0
                ? " | Terlambat {$overdueDays} hari, denda Rp " . number_format($lateFee, 0, ',', '.')
                : '';
            $diskonInfo  = $hasManualDiscount
                ? " | Diskon manual \"{$discountName}\": -Rp " . number_format($discountAmount, 0, ',', '.')
                : '';
            $rusakInfo   = $totalDamageFee > 0
                ? " | Denda rusak/hilang: Rp " . number_format($totalDamageFee, 0, ',', '.')
                : '';
            $sitaInfo    = $guaranteeClaimed
                ? " | Jaminan disita (item: " . implode(', ', $claimedItemNames) . ")"
                : '';

            if ($rental->payment_status !== 'paid') {
                // MASIH ADA KEKURANGAN: berhenti di sini. Barang belum ditandai
                // dikembalikan, rental_status tidak berubah. Controller akan
                // menampilkan pesan "harus bayar dulu" + nominal kekurangan.
                $this->logActivity('assess_return', $rental,
                    "Kondisi barang {$rental->invoice_number} dicatat ({$packageName}){$dendaInfo}{$diskonInfo}{$rusakInfo}{$sitaInfo} | Kondisi: {$worstCondition} | MENUNGGU PEMBAYARAN sisa Rp " . number_format($rental->remaining_amount, 0, ',', '.')
                );

                return $rental->fresh();
            }

            // TIDAK ADA KEKURANGAN (atau baru saja lunas) -> langsung finalisasi.
            return $this->finalizeReturn($rental);
        });
    }

    /**
     * FASE 2: FINALISASI pengembalian barang.
     *
     * Dipanggil HANYA ketika tagihan sudah 100% lunas (payment_status ===
     * 'paid') untuk rental yang sudah melalui assessment (return_assessed_at
     * terisi). Di sinilah barang BENAR-BENAR ditandai "sudah dikembalikan":
     * is_returned = true, masuk antrean laundry, rental_status maju.
     *
     * Dipanggil dari 2 tempat:
     *   1. processReturn() di atas, kalau begitu dinilai ternyata tidak ada
     *      kekurangan bayar sama sekali (kasus paling umum).
     *   2. processPayment(), begitu pembayaran yang masuk membuat tagihan
     *      lunas untuk rental yang assessment-nya masih menggantung.
     */
    public function finalizeReturn(Rental $rental): Rental
    {
        return DB::transaction(function () use ($rental) {
            $rental = Rental::with('items')->lockForUpdate()->find($rental->id);

            // Guard: hanya boleh finalisasi kalau memang sudah dinilai & lunas.
            if (is_null($rental->return_assessed_at) || $rental->payment_status !== 'paid') {
                return $rental;
            }
            // Guard: jangan finalisasi dobel kalau sudah pernah (idempotent).
            if (!in_array($rental->rental_status, [Rental::STATUS_ACTIVE, Rental::STATUS_OVERDUE], true)) {
                return $rental;
            }

            $now   = now('Asia/Jakarta');
            $today = $now->copy()->startOfDay();

            $priority       = self::CONDITION_PRIORITY;
            $worstCondition = 'good';
            $worstNotes     = null;
            $assessedItems  = $rental->items->whereNotNull('return_condition')->where('is_returned', false);

            foreach ($assessedItems as $rentalItem) {
                $condition = $rentalItem->return_condition ?? 'good';

                if (($priority[$condition] ?? 0) > ($priority[$worstCondition] ?? 0)) {
                    $worstCondition = $condition;
                    $worstNotes     = $rentalItem->return_notes;
                }

                $rentalItem->update([
                    'is_returned' => true,
                    'returned_at' => $now,
                ]);

                Laundry::create([
                    'transaksi_id'    => $rental->id,
                    'produk_id'       => $rentalItem->product_id,
                    'status'          => Laundry::STATUS_MENUNGGU_LAUNDRY,
                    'dikembalikan_at' => $now,
                ]);
            }

            // ── Simpan ke rental_returns (baru sekarang, karena baru sekarang
            // barang benar-benar dianggap "dikembalikan") ───────────────────
            RentalReturn::create([
                'rental_id'    => $rental->id,
                'returned_at'  => $today->toDateString(),
                'late_days'    => (int) $rental->overdue_days,
                'late_fee'     => (float) $rental->late_fee,
                'condition'    => $worstCondition,
                'return_notes' => $worstNotes,
            ]);

            $rental->update([
                'rental_status'      => Rental::STATUS_MENUNGGU_LAUNDRY,
                'actual_return_date' => $today->toDateString(),
                'returned_at'        => $now,
                // FITUR BARU: catat staf yang BENAR-BENAR memproses
                // pengembalian ini (bisa beda orang dari yang membuat
                // transaksi sewa awal) -> ditampilkan di nota sebagai
                // "Dikembalikan oleh". Auth::id() di sini selalu tepat
                // karena finalizeReturn() hanya jalan lewat aksi staf yang
                // sedang login (submit form retur, atau memproses
                // pembayaran kekurangan yang men-trigger auto-finalize).
                'returned_by'        => Auth::id(),
            ]);

            $stillHasOngoingRental = Rental::where('customer_id', $rental->customer_id)
                ->where('id', '!=', $rental->id)
                ->whereIn('rental_status', [
                    Rental::STATUS_WAITING,
                    Rental::STATUS_ACTIVE,
                    Rental::STATUS_OVERDUE,
                ])
                ->exists();

            if (!$stillHasOngoingRental) {
                $rental->guarantees()->where('status', 'held')->update(['status' => 'returned', 'returned_at' => $now]);
            }

            $ktpForfeited = $rental->guarantees()
                ->where('status', 'forfeited')
                ->where('type', 'ktp')
                ->exists();

            if (!$stillHasOngoingRental && !$ktpForfeited) {
                $customer = $rental->customer;
                if ($customer && $customer->id_photo) {
                    try {
                        Storage::disk('public')->delete($customer->id_photo);
                    } catch (\Throwable $e) {
                    }
                    $customer->update([
                        'id_photo'                => null,
                        'id_photo_type'           => null,
                        'id_photo_reusable_until' => null,
                    ]);
                }
            }

            $totalDamageFee = (float) $rental->items->sum('damage_fee');
            $rusakInfo = $totalDamageFee > 0
                ? " | Denda rusak/hilang: Rp " . number_format($totalDamageFee, 0, ',', '.')
                : '';

            $this->logActivity('return_rental', $rental,
                "Pengembalian {$rental->invoice_number} difinalisasi (lunas){$rusakInfo} | Kondisi: {$worstCondition}"
            );

            return $rental->fresh();
        });
    }


    // ─── Cancel Rental ────────────────────────────────────────────────────────

    /**
     * Membatalkan penyewaan dengan 2 alur berbeda tergantung fase penyewaan:
     *
     * 1) BELUM AKTIF (rental_status === waiting, artinya belum lunas/belum
     *    diserahkan ke customer): barang belum pernah dipakai, jadi stok
     *    langsung dikembalikan ke inventory (tanpa laundry) dan tagihan
     *    (total_amount) dinolkan — customer tidak berhutang apa pun.
     *
     * 2) SUDAH AKTIF/OVERDUE (barang sedang dipegang/dipakai customer):
     *    barang tetap harus dicuci meski batal di tengah jalan, jadi item
     *    dikirim ke antrian laundry (sama seperti retur normal) alih-alih
     *    langsung kembali ke stok, DAN wajib ada biaya laundry manual
     *    (`laundry_fee`) yang ditambahkan ke total_amount sebagai kompensasi.
     *
     * $data: ['reason' => ?string, 'laundry_fee' => ?float]
     */
    public function cancelRental(Rental $rental, array $data = []): Rental
    {
        return DB::transaction(function () use ($rental, $data) {
            $wasActive = !$rental->is_cancellable_without_fee; // true jika active/overdue

            $laundryFee = 0.0;

            if ($wasActive) {
                // ── Barang sudah dipakai: wajib laundry + biaya manual ──────────
                $laundryFee = max(0, (float) ($data['laundry_fee'] ?? 0));

                foreach ($rental->items as $item) {
                    Laundry::create([
                        'transaksi_id'    => $rental->id,
                        'produk_id'       => $item->product_id,
                        'status'          => Laundry::STATUS_MENUNGGU_LAUNDRY,
                        'dikembalikan_at' => now('Asia/Jakarta'),
                    ]);
                }

                $newTotal = (float) $rental->total_amount + $laundryFee;
            } else {
                // ── Belum pernah dipakai: stok langsung balik, tagihan nol ──────
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

                $newTotal = 0.0;

                // ── Aturan reuse KTP setelah pembatalan sebelum aktif ───────────
                // Kalau customer masih punya sewa AKTIF LAIN (KTP fisiknya masih
                // tertahan di toko untuk sewa itu), foto KTP tersimpan masih boleh
                // dipakai ulang otomatis, tapi cuma 30 menit dari sekarang (untuk
                // jaga-jaga kalau staf mau langsung buatkan transaksi baru).
                // Kalau TIDAK ada sewa aktif lain sama sekali, berarti tidak ada
                // alasan KTP fisik masih di toko -> reuse langsung dimatikan,
                // customer wajib scan/upload ulang di transaksi berikutnya.
            }

            $rental->update([
                'rental_status'      => Rental::STATUS_CANCELLED,
                'cancel_reason'      => $data['reason'] ?? null,
                'cancelled_at'       => now('Asia/Jakarta'),
                'cancelled_by'       => Auth::id(),
                'total_amount'       => $newTotal,
                'cancel_laundry_fee' => $laundryFee,
                // FIX: sebelumnya dihitung murni dari (paid_amount >= total_amount),
                // yang keliru untuk pembatalan SEBELUM bayar — karena total_amount
                // sengaja dinolkan, rumus itu selalu bernilai true (0 >= 0) sehingga
                // badge menampilkan "LUNAS" padahal customer belum pernah bayar sama
                // sekali. "Lunas" seharusnya hanya untuk yang benar-benar SUDAH
                // membayar penuh, bukan untuk tagihan yang dihapuskan karena batal.
                'payment_status'     => match (true) {
                    // Belum aktif & batal sebelum bayar -> tidak pernah "lunas",
                    // tetap tercatat belum bayar (tagihannya sendiri sudah nol).
                    !$wasActive => Rental::PAYMENT_UNPAID,
                    (float) $rental->paid_amount >= $newTotal => Rental::PAYMENT_PAID,
                    default => Rental::PAYMENT_PARTIAL,
                },
            ]);

            $customer = $rental->customer;
            if ($customer) {
                $hasOtherActiveRental = $customer->rentals()
                    ->where('id', '!=', $rental->id)
                    ->where(function ($q) {
                        $q->where('rental_status', Rental::STATUS_ACTIVE)
                          ->orWhere(function ($q2) {
                              $q2->where('rental_status', Rental::STATUS_OVERDUE)
                                 ->where('payment_status', Rental::PAYMENT_PAID);
                          });
                    })
                    ->exists();

                if ($hasOtherActiveRental) {
                    $customer->update([
                        'id_photo_reusable_until' => now('Asia/Jakarta')->addMinutes(30),
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

            $this->logActivity('cancel_rental', $rental,
                $wasActive
                    ? "Penyewaan {$rental->invoice_number} dibatalkan saat AKTIF — barang masuk antrian laundry, biaya laundry Rp " . number_format($laundryFee, 0, ',', '.')
                    : "Penyewaan {$rental->invoice_number} dibatalkan sebelum aktif — stok langsung dikembalikan, tagihan Rp 0"
            );

            return $rental->fresh();
        });
    }

    // ─── Overdue ──────────────────────────────────────────────────────────────

    public function updateOverdueRentals(): int
    {
        return Rental::where('rental_status', Rental::STATUS_ACTIVE)
            ->whereDate('return_due_date', '<', now('Asia/Jakarta')->toDateString())
            ->update(['rental_status' => Rental::STATUS_OVERDUE]);
    }

    // ─── QR Code ─────────────────────────────────────────────────────────────

    public function generateQrCode(Rental $rental): void
    {
        $qrData   = route('rentals.scan', $rental->invoice_number);
        $path     = 'qrcodes/rentals/' . $rental->invoice_number . '.svg';
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $svg = QrCode::format('svg')->size(300)->margin(2)->generate($qrData);
        file_put_contents($fullPath, $svg);

        $rental->update(['qr_code' => $path]);
    }

    // ─── Activity Log ────────────────────────────────────────────────────────

    protected function logActivity(string $action, Rental $rental, string $description): void
    {
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()?->branch_id,
            'action'      => $action,
            'model_type'  => Rental::class,
            'model_id'    => $rental->id,
            'description' => $description,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
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

            // ── Buat rental ───────────────────────────────────────────────────
            $rental = Rental::create([
                'invoice_number'  => $this->generateInvoiceNumber($user->branch_id),
                'branch_id'       => $user->branch_id,
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

            // FIX: rental_status hanya boleh otomatis maju ke ACTIVE kalau rental
            // masih dalam siklus SEBELUM retur (waiting/active/overdue). Sebelumnya,
            // melunasi denda keterlambatan SETELAH barang dikembalikan (status sudah
            // menunggu_laundry/dalam_laundry/siap_disewakan/returned) ikut memaksa
            // rental_status kembali jadi 'active' — seolah barang disewa ulang.
            $preReturnStatuses = [Rental::STATUS_WAITING, Rental::STATUS_ACTIVE, Rental::STATUS_OVERDUE];
            $nextStatus = ($isFullyPaid && in_array($rental->rental_status, $preReturnStatuses, true))
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

            return $payment;
        });
    }

    // ─── Process Return ───────────────────────────────────────────────────────

    /**
     * Hitung denda paket:
     *   1. Ambil paket dari rental
     *   2. Hitung hari terlambat
     *   3. Denda = subtotal × (penalty_percent/100) × hari_terlambat
     *   4. Cap dengan max_penalty_percent jika ada
     *
     * Fallback: jika rental tidak punya paket, pakai fine_per_day dari settings.
     */
    public function processReturn(Rental $rental, array $data = []): Rental
    {
        return DB::transaction(function () use ($rental, $data) {
            $now     = now('Asia/Jakarta');
            $today   = $now->copy()->startOfDay();
            $dueDate = Carbon::parse($rental->return_due_date, 'Asia/Jakarta')->startOfDay();

            // ── Keterlambatan (informasi hari saja) & denda manual ─────────────
            $overdueDays = 0;
            if ($today->gt($dueDate)) {
                $overdueDays = (int) $dueDate->diffInDays($today);
            }
            $lateFee = isset($data['late_fee']) && $data['late_fee'] !== ''
                ? max(0, (float) $data['late_fee'])
                : 0;

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

            foreach ($items as $itemData) {
                $rentalItem = RentalItem::find($itemData['rental_item_id']);
                if (!$rentalItem) continue;

                $condition = $itemData['condition'] ?? 'good';
                $notes     = $itemData['notes'] ?? null;

                $penaltyResolution = $itemData['penalty_resolution'] ?? null;
                $itemDamageFee     = 0;

                if (in_array($condition, ['damaged', 'lost'], true)) {
                    if ($penaltyResolution === 'charge_double') {
                        $itemDamageFee = (float) $rentalItem->subtotal * 2;
                        $totalDamageFee += $itemDamageFee;
                    } elseif ($penaltyResolution === 'claim_guarantee') {
                        $guaranteeClaimed = true;
                        $claimedItemNames[] = $rentalItem->product_name;
                    }
                }

                $rentalItem->update([
                    'is_returned'      => true,
                    'returned_at'      => $now,
                    'return_condition' => $condition,
                    'return_notes'     => $notes,
                    'damage_fee'       => $itemDamageFee,
                ]);

                Laundry::create([
                    'transaksi_id'    => $rental->id,
                    'produk_id'       => $rentalItem->product_id,
                    'status'          => Laundry::STATUS_MENUNGGU_LAUNDRY,
                    'dikembalikan_at' => $now,
                ]);

                if (($priority[$condition] ?? 0) > ($priority[$worstCondition] ?? 0)) {
                    $worstCondition = $condition;
                    $worstNotes     = $notes;
                }
            }

            // Fallback jika items kosong
            if (empty($items)) {
                foreach ($rental->items as $item) {
                    $item->update([
                        'is_returned'      => true,
                        'returned_at'      => $now,
                        'return_condition' => 'good',
                    ]);
                    Laundry::create([
                        'transaksi_id'    => $rental->id,
                        'produk_id'       => $item->product_id,
                        'status'          => Laundry::STATUS_MENUNGGU_LAUNDRY,
                        'dikembalikan_at' => $now,
                    ]);
                }
            }

            // ── Simpan ke rental_returns ──────────────────────────────────────
            RentalReturn::create([
                'rental_id'    => $rental->id,
                'returned_at'  => $today->toDateString(),
                'late_days'    => $overdueDays,
                'late_fee'     => $lateFee,
                'condition'    => $worstCondition,
                'return_notes' => $worstNotes,
            ]);

            // ── Update rentals ────────────────────────────────────────────────
            if ($hasManualDiscount) {
                // Formula BARU (spec): total = subtotal + late_fee - discount, never negative.
                $totalWithFees = max(0, $subtotal + $lateFee - $discountAmount);
            } else {
                // Perilaku LAMA — tidak diubah sama sekali kalau tidak ada diskon manual dikirim.
                $totalWithFees = $rental->total_amount + $lateFee;
            }

            $totalWithFees += $totalDamageFee;

            $updatePayload = [
                'rental_status'      => Rental::STATUS_MENUNGGU_LAUNDRY,
                'actual_return_date' => $today->toDateString(),
                'returned_at'        => $now,
                'late_fee'           => $lateFee,
                'late_fee_note'      => $data['late_fee_note'] ?? null,
                'overdue_days'       => $overdueDays,
                'total_amount'       => $totalWithFees,
                // FIX: sebelumnya jika belum lunas kode ini mempertahankan payment_status
                // LAMA (yang biasanya 'paid', karena syarat retur mengharuskan lunas dulu).
                // Akibatnya setelah denda ditambahkan, status tetap 'paid' walau saldo
                // sebenarnya belum 0 — sehingga tombol "Bayar" untuk pelunasan denda
                // tidak pernah muncul lagi di UI manapun. Sekarang dihitung ulang murni
                // dari perbandingan total vs. paid_amount.
                'payment_status'     => $totalWithFees <= $rental->paid_amount ? 'paid' : 'partial',
            ];

            if ($hasManualDiscount) {
                $updatePayload['discount']              = $discountAmount;
                $updatePayload['discount_name']          = $discountName;
                $updatePayload['discount_description']   = $discountDescription;
                $updatePayload['discount_type']           = $discountType;
                $updatePayload['discount_value']          = $discountValueRaw;
            }

            $rental->update($updatePayload);

            if ($guaranteeClaimed) {
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

            $rental->guarantees()->where('status', 'held')->update(['status' => 'returned', 'returned_at' => $now]);

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

            $this->logActivity('return_rental', $rental,
                "Pengembalian {$rental->invoice_number} ({$packageName}){$dendaInfo}{$diskonInfo}{$rusakInfo}{$sitaInfo} | Kondisi: {$worstCondition}"
            );

            $stillHasOngoingRental = Rental::where('customer_id', $rental->customer_id)
                ->where('id', '!=', $rental->id)
                ->whereIn('rental_status', [
                    Rental::STATUS_WAITING,
                    Rental::STATUS_ACTIVE,
                    Rental::STATUS_OVERDUE,
                ])
                ->exists();

            $ktpForfeited = $guaranteeClaimed && $rental->guarantees()
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
                    $customer->update(['id_photo' => null, 'id_photo_type' => null]);
                }
            }

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
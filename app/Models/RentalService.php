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
                'reference_number' => $data['reference_number'] ?? null,
                'type'             => $data['type'] ?? 'rental',
                'notes'            => $data['notes'] ?? null,
                'paid_at'          => now('Asia/Jakarta'),
            ]);

            $newPaidAmount = $rental->paid_amount + (float) $data['amount'];
            $rental->update([
                'paid_amount'    => $newPaidAmount,
                'payment_status' => $newPaidAmount >= $rental->total_amount ? 'paid' : 'partial',
                'rental_status'  => $newPaidAmount >= $rental->total_amount
                                        ? Rental::STATUS_ACTIVE
                                        : $rental->rental_status,
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

            // ── Hitung keterlambatan ──────────────────────────────────────────
            $lateFee     = 0;
            $overdueDays = 0;

            if ($today->gt($dueDate)) {
                $overdueDays = (int) $dueDate->diffInDays($today);

                // Pakai denda paket jika ada; fallback ke settings
                if ($rental->package) {
                    $lateFee = $rental->package->calculatePenalty(
                        (float) $rental->subtotal,
                        $overdueDays
                    );
                } else {
                    // Legacy: flat per hari dari settings
                    $finePerDay = (int) DB::table('settings')
                        ->where('key', 'fine_per_day')
                        ->value('value') ?? 10000;
                    $lateFee = $overdueDays * $finePerDay;
                }
            }

            // ── Kondisi terburuk dari semua item ─────────────────────────────
            $priority       = self::CONDITION_PRIORITY;
            $worstCondition = 'good';
            $worstNotes     = null;
            $items          = $data['items'] ?? [];

            foreach ($items as $itemData) {
                $rentalItem = RentalItem::find($itemData['rental_item_id']);
                if (!$rentalItem) continue;

                $condition = $itemData['condition'] ?? 'good';
                $notes     = $itemData['notes'] ?? null;

                $rentalItem->update([
                    'is_returned'      => true,
                    'returned_at'      => $now,
                    'return_condition' => $condition,
                    'return_notes'     => $notes,
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
            $totalWithFees = $rental->total_amount + $lateFee;

            $rental->update([
                'rental_status'      => Rental::STATUS_MENUNGGU_LAUNDRY,
                'actual_return_date' => $today->toDateString(),
                'returned_at'        => $now,
                'late_fee'           => $lateFee,
                'overdue_days'       => $overdueDays,
                'total_amount'       => $totalWithFees,
                'payment_status'     => $totalWithFees <= $rental->paid_amount ? 'paid' : $rental->payment_status,
            ]);

            $rental->guarantees()->update(['status' => 'returned', 'returned_at' => $now]);

            $packageName = $rental->package?->name ?? 'tanpa paket';
            $dendaInfo   = $overdueDays > 0
                ? " | Terlambat {$overdueDays} hari, denda Rp " . number_format($lateFee, 0, ',', '.')
                : '';

            $this->logActivity('return_rental', $rental,
                "Pengembalian {$rental->invoice_number} ({$packageName}){$dendaInfo} | Kondisi: {$worstCondition}"
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
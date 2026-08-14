<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rental extends Model
{
    use SoftDeletes;

    protected $table = 'rentals';

        protected static function booted(): void
    {
        static::creating(function (Rental $rental) {
            if (empty($rental->public_token)) {
                $rental->public_token = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'invoice_number', 'branch_id', 'customer_id', 'created_by', 'returned_by',
        'package_id',                          // ← BARU
        'rental_date', 'return_due_date', 'actual_return_date', 'returned_at',
        'duration_days',
        'subtotal', 'discount', 'late_fee', 'late_fee_note', 'late_fee_confirmed_at', 'return_assessed_at', 'cancel_laundry_fee', 'total_amount', 'paid_amount',
        'discount_name', 'discount_description', 'discount_type', 'discount_value', // ← BARU: metadata diskon manual (proses retur)
        'overdue_days',
        'payment_status', 'rental_status',
        'notes', 'qr_code',
        'cancel_reason', 'cancelled_by', 'cancelled_at',
    ];

    protected $casts = [
        'rental_date'        => 'date',
        'return_due_date'    => 'date',
        'actual_return_date' => 'date',
        'returned_at'        => 'datetime',
        'cancelled_at'       => 'datetime',
        'late_fee_confirmed_at' => 'datetime',
        'return_assessed_at'    => 'datetime',
        'subtotal'           => 'decimal:2',
        'discount'           => 'decimal:2',
        'discount_value'     => 'decimal:2', // ← BARU
        'late_fee'           => 'decimal:2',
        'total_amount'       => 'decimal:2',
        'paid_amount'        => 'decimal:2',
        'overdue_days'       => 'integer',
        'duration_days'      => 'integer',
    ];

    // ─── Status Constants ─────────────────────────────────────────────────────

    const STATUS_WAITING            = 'waiting';
    const STATUS_ACTIVE             = 'active';
    const STATUS_OVERDUE            = 'overdue';
    const STATUS_MENUNGGU_LAUNDRY   = 'menunggu_laundry';
    const STATUS_DALAM_LAUNDRY      = 'dalam_laundry';
    const STATUS_SIAP_DISEWAKAN     = 'siap_disewakan';
    const STATUS_RETURNED           = 'returned';
    const STATUS_CANCELLED          = 'cancelled';

    const PAYMENT_UNPAID  = 'unpaid';
    const PAYMENT_PARTIAL = 'partial';
    const PAYMENT_PAID    = 'paid';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_WAITING          => 'Menunggu Pembayaran',
            self::STATUS_ACTIVE           => 'Sedang Disewa',
            self::STATUS_OVERDUE          => 'Terlambat',
            self::STATUS_MENUNGGU_LAUNDRY => 'Menunggu Laundry',
            self::STATUS_DALAM_LAUNDRY    => 'Dalam Laundry',
            self::STATUS_SIAP_DISEWAKAN   => 'Siap Disewakan',
            self::STATUS_RETURNED         => 'Selesai',
            self::STATUS_CANCELLED        => 'Dibatalkan',
        ];
    }

    public static function paymentStatusLabels(): array
    {
        return [
            self::PAYMENT_UNPAID  => 'Belum Bayar',
            self::PAYMENT_PARTIAL => 'Bayar Sebagian',
            self::PAYMENT_PAID    => 'Lunas',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * FITUR BARU: admin/staf yang benar-benar MEMPROSES PENGEMBALIAN barang
     * (menerima barang, cek kondisi, finalisasi) — bisa berbeda dari
     * createdBy() (staf yang membuat transaksi sewa di awal). Diisi otomatis
     * di RentalService::finalizeReturn(). Null selama barang belum
     * dikembalikan.
     */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** Paket sewa yang digunakan — BARU */
    public function package(): BelongsTo
    {
        return $this->belongsTo(RentalPackage::class, 'package_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function guarantees(): HasMany
    {
        return $this->hasMany(Guarantee::class);
    }

    public function returnRecord(): HasOne
    {
        return $this->hasOne(RentalReturn::class);
    }

    public function laundries(): HasMany
    {
        return $this->hasMany(Laundry::class, 'transaksi_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->rental_status] ?? $this->rental_status;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->rental_status) {
            self::STATUS_WAITING          => 'yellow',
            self::STATUS_ACTIVE           => 'blue',
            self::STATUS_OVERDUE          => 'red',
            self::STATUS_MENUNGGU_LAUNDRY,
            self::STATUS_DALAM_LAUNDRY    => 'gray',
            self::STATUS_SIAP_DISEWAKAN   => 'gold',
            self::STATUS_RETURNED         => 'green',
            self::STATUS_CANCELLED        => 'red',
            default                       => 'gray',
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::paymentStatusLabels()[$this->payment_status] ?? $this->payment_status;
    }

        public function getDiscountTypeLabelAttribute(): ?string
    {
        return match ($this->discount_type) {
            'nominal' => 'Nominal',
            'percent' => 'Persen',
            default   => null,
        };
    }

        public function getHasManualDiscountAttribute(): bool
    {
        return !is_null($this->discount_type) && (float) $this->discount > 0;
    }

        public function getTotalDamageFeeAttribute(): float
    {
        return (float) $this->items->sum('damage_fee');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    /**
     * FITUR: Denda harus dibayar dulu sebelum barang bisa dikembalikan.
     *
     * true  = rental sedang overdue TAPI staf belum menentukan nominal denda
     *         sama sekali → form pengembalian fisik harus disembunyikan,
     *         staf diarahkan ke form "Tentukan Denda" dulu.
     */
    public function getNeedsLateFeeConfirmationAttribute(): bool
    {
        return $this->rental_status === self::STATUS_OVERDUE
            && is_null($this->late_fee_confirmed_at);
    }

    /**
     * true = denda sudah ditentukan staf, TAPI belum lunas dibayar →
     *        form pengembalian fisik harus disembunyikan, staf diarahkan
     *        untuk menyelesaikan pembayaran denda dulu.
     */
    public function getNeedsLateFeePaymentAttribute(): bool
    {
        return $this->rental_status === self::STATUS_OVERDUE
            && !is_null($this->late_fee_confirmed_at)
            // FIX: begitu assessment kondisi barang sudah dimulai
            // (return_assessed_at terisi), kekurangan bayar yang tersisa
            // adalah soal tagihan RETUR (denda rusak/hilang) — bukan lagi
            // soal denda telat, walau kolom payment_status yang dipakai
            // sama. Tanpa syarat ini, tombol "Bayar Denda" (telat) bisa
            // salah muncul padahal yang perlu dibayar sebenarnya denda
            // rusak/hilang (lihat needs_return_payment).
            && is_null($this->return_assessed_at)
            && $this->payment_status !== self::PAYMENT_PAID;
    }

    /**
     * true = barang BOLEH diproses pengembalian fisiknya sekarang.
     * Dipakai baik di Blade (tampilkan/sembunyikan form) maupun sebagai
     * guard terakhir di backend (RentalController::processReturn) supaya
     * aturan ini tidak bisa dilewati walau seseorang POST langsung ke API.
     */
    public function getCanBeReturnedAttribute(): bool
    {
        if (!in_array($this->rental_status, [self::STATUS_ACTIVE, self::STATUS_OVERDUE], true)) {
            return false;
        }

        // FITUR BARU: kalau kondisi barang sudah pernah dinilai (assessment)
        // tapi tagihannya belum lunas, form "catat kondisi barang" TIDAK
        // ditampilkan lagi -> arahkan ke pembayaran kekurangan dulu
        // (lihat needs_return_payment).
        if (!is_null($this->return_assessed_at) && $this->payment_status !== self::PAYMENT_PAID) {
            return false;
        }

        if ($this->rental_status === self::STATUS_OVERDUE) {
            return !$this->needs_late_fee_confirmation
                && !$this->needs_late_fee_payment;
        }

        // Tidak overdue: syarat lama tetap berlaku — lunas dulu baru bisa dikembalikan.
        return $this->payment_status === self::PAYMENT_PAID;
    }

    /**
     * FITUR BARU: "Barang tidak selesai dikembalikan sampai tagihan lunas".
     *
     * true = staf sudah mencatat kondisi barang (assessment) — misalnya ada
     * yang rusak/hilang dengan opsi bayar tunai — TAPI hasil tagihannya
     * (subtotal + denda telat + denda rusak/hilang - diskon) masih ada
     * kekurangan. Barang BELUM ditandai selesai dikembalikan & rental_status
     * BELUM maju ke laundry sampai ini lunas.
     */
    public function getNeedsReturnPaymentAttribute(): bool
    {
        return !is_null($this->return_assessed_at)
            && in_array($this->rental_status, [self::STATUS_ACTIVE, self::STATUS_OVERDUE], true)
            && $this->payment_status !== self::PAYMENT_PAID;
    }

    /**
     * true = assessment sudah dilakukan & tagihan sudah lunas, tapi karena
     * suatu sebab belum sempat difinalisasi otomatis -> tombol
     * "Selesaikan Pengembalian" manual boleh dipakai (fallback/jaga-jaga).
     * Pada alur normal ini jarang kepakai karena finalisasi terjadi
     * otomatis begitu pembayaran kekurangan masuk (lihat
     * RentalService::processPayment).
     */
    public function getCanFinalizeReturnAttribute(): bool
    {
        return !is_null($this->return_assessed_at)
            && in_array($this->rental_status, [self::STATUS_ACTIVE, self::STATUS_OVERDUE], true)
            && $this->payment_status === self::PAYMENT_PAID;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->rental_status === self::STATUS_OVERDUE
            || (
                in_array($this->rental_status, [self::STATUS_ACTIVE])
                && $this->return_due_date
                && $this->return_due_date->startOfDay()->lt(now()->startOfDay())
            );
    }

    /**
     * Estimasi denda LIVE untuk rental yang belum dikembalikan.
     * Berguna di dashboard/show untuk menampilkan angka denda yang akan ditagih.
     */
    public function getLiveLateFeeAttribute(): float
    {
        return (float) $this->late_fee;
    }

        public function getLiveLateDaysAttribute(): int
    {
        if (!$this->return_due_date) return 0;

        $dueDate = $this->return_due_date->startOfDay();
        $today   = now()->startOfDay();

        return $today->gt($dueDate) ? (int) $dueDate->diffInDays($today) : 0;
    }

    /**
     * Dipakai RentalService::cancelRental() untuk menentukan apakah
     * pembatalan ini butuh biaya laundry (barang sudah sempat di tangan
     * customer) atau bisa langsung dibatalkan tanpa biaya (belum pernah
     * dipakai sama sekali, status masih 'waiting').
     */
    public function getIsCancellableWithoutFeeAttribute(): bool
    {
        return !in_array($this->rental_status, ['active', 'overdue']);
    }

    public function getDueAlertAttribute(): ?array
    {
        if (!in_array($this->rental_status, ['active', 'overdue']) || !$this->return_due_date) {
            return null;
        }

        $today = now()->startOfDay();
        $due   = $this->return_due_date->copy()->startOfDay();

        if ($due->lt($today)) {
            $daysLate = (int) $due->diffInDays($today);
            return ['level' => 'overdue', 'label' => "{$daysLate} hari telat", 'days' => $daysLate];
        }
        if ($due->eq($today)) {
            return ['level' => 'today', 'label' => 'Jatuh tempo hari ini', 'days' => 0];
        }
        if ($due->eq($today->copy()->addDay())) {
            return ['level' => 'tomorrow', 'label' => 'Jatuh tempo besok', 'days' => 1];
        }

        $daysLeft = (int) $today->diffInDays($due);
        if ($daysLeft <= 3) {
            return ['level' => 'soon', 'label' => "{$daysLeft} hari lagi", 'days' => $daysLeft];
        }

        return null;
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('rental_status', self::STATUS_ACTIVE);
    }

    public function scopeOverdue($query)
    {
        return $query->where('rental_status', self::STATUS_OVERDUE);
    }

    public function scopeByPackage($query, int $packageId)
    {
        return $query->where('package_id', $packageId);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('payment_status', [self::PAYMENT_UNPAID, self::PAYMENT_PARTIAL])
                     ->whereNotIn('rental_status', [self::STATUS_CANCELLED]);
    }

    /**
     * BARU (refactor): dipakai untuk mengganti pola berulang
     * `when($branchId, fn($q) => $q->where('branch_id', $branchId))`
     * yang sebelumnya ditulis ulang di ~8 tempat berbeda
     * (RentalController & ReportController).
     * null = tidak difilter (mis. super_admin lihat semua cabang).
     */
    public function scopeForBranch($query, ?int $branchId)
    {
        return $branchId !== null ? $query->where('branch_id', $branchId) : $query;
    }

    /**
     * BARU (refactor): dipakai untuk mengganti pola berulang
     * `if ($request->filled('start_date')) $query->whereDate('rental_date', '>=', ...)`
     * yang sebelumnya ditulis ulang di ~6 tempat berbeda di ReportController.
     */
    public function scopeRentalDateBetween($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->whereDate('rental_date', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('rental_date', '<=', $to));
    }
}
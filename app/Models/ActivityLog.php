<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ActivityLog — SATU model untuk semua log aktivitas sistem.
 *
 * Menggantikan AuditLog terpisah. Semua pencatatan cukup via model ini:
 *   ActivityLog::create([...])
 *   atau via Observer (ProductObserver, RentalObserver, PaymentObserver)
 */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'branch_id', 'action', 'model_type', 'model_id',
        'description', 'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'action_label',
        'action_badge_class',
        'model_name',
        'has_diff',
    ];

    // ─── Label aksi ───────────────────────────────────────────────────────────

    const ACTION_LABELS = [
        // Rental
        'create_rental'          => 'Buat Penyewaan',
        'update_rental'          => 'Edit Penyewaan',
        'cancel_rental'          => 'Batalkan Penyewaan',
        'return_rental'          => 'Barang Dikembalikan',
        'assess_return'          => 'Kondisi Barang Dicatat (Menunggu Pembayaran)',
        'set_late_fee'           => 'Tentukan Denda Keterlambatan',
        'delete_rental'          => 'Hapus Penyewaan',
        // Pembayaran
        'process_payment'        => 'Proses Pembayaran',
        // Produk
        'create_product'         => 'Tambah Produk',
        'update_product'         => 'Edit Produk',
        'delete_product'         => 'Hapus Produk',
        'update_stock'           => 'Update Stok',
        // Paket
        'create_package'         => 'Buat Paket Sewa',
        'update_package'         => 'Edit Paket Sewa',
        'delete_package'         => 'Hapus Paket Sewa',
        // Jaminan
        'return_guarantee'       => 'Kembalikan Jaminan',
        'upload_guarantee_photo' => 'Upload Foto Jaminan',
        // Customer
        'create_customer'        => 'Tambah Customer',
        'update_customer'        => 'Edit Customer',
        'delete_customer'        => 'Hapus Customer',
        'delete_customer_force'  => 'Hapus Customer Permanen',
        'export_customers'       => 'Export Data Customer',
        // User
        'create_user'            => 'Tambah Pengguna',
        'update_user'            => 'Edit Pengguna',
        'delete_user'            => 'Hapus Pengguna',
        // Laundry
        'mulai_laundry'          => 'Mulai Laundry',
        'selesai_laundry'        => 'Selesai Laundry',
        // Auth
        'login'                  => 'Login',
        'logout'                 => 'Logout',
    ];

    const FIELD_LABELS = [
        'rental_status'      => 'Status Sewa',
        'payment_status'     => 'Status Bayar',
        'total_amount'       => 'Total',
        'paid_amount'        => 'Sudah Dibayar',
        'late_fee'           => 'Denda',
        'discount'           => 'Diskon',
        'stock_available'    => 'Stok Tersedia',
        'stock_total'        => 'Total Stok',
        'status'             => 'Status',
        'name'               => 'Nama',
        'rental_price'       => 'Harga Sewa',
        'duration_days'      => 'Durasi (hari)',
        'penalty_percent'    => 'Denda (%)',
        'is_active'          => 'Aktif',
        'overdue_days'       => 'Hari Terlambat',
        'actual_return_date' => 'Tgl Kembali Aktual',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action]
            ?? ucwords(str_replace('_', ' ', $this->action));
    }

    public function getModelNameAttribute(): string
    {
        if (!$this->model_type) return '-';
        return class_basename($this->model_type);
    }

    public function getActionBadgeClassAttribute(): string
    {
        $a = $this->action;
        if (str_contains($a, 'delete') || str_contains($a, 'cancel')) return 'badge-red';
        if (str_contains($a, 'update') || str_contains($a, 'edit'))   return 'badge-amber';
        if (str_contains($a, 'create'))                                return 'badge-green';
        if (in_array($a, ['return_rental','return_guarantee','selesai_laundry','process_payment'])) return 'badge-blue';
        return 'badge-gray';
    }

    public function getHasDiffAttribute(): bool
    {
        return !empty($this->old_values) || !empty($this->new_values);
    }

    /**
     * MEANINGFUL CHANGES — diff old vs new siap tampil di blade.
     *
     * Contoh output:
     * [
     *   ['field' => 'Status Sewa', 'old' => 'active', 'new' => 'returned', 'is_money' => false],
     *   ['field' => 'Denda',       'old' => 'Rp 0',   'new' => 'Rp 50.000', 'is_money' => true],
     * ]
     */
    public function getMeaningfulChangesAttribute(): array
    {
        $old  = $this->old_values ?? [];
        $new  = $this->new_values ?? [];
        $skip = ['updated_at','created_at','deleted_at','qr_code','remember_token','password'];
        $moneyFields = ['total_amount','paid_amount','late_fee','discount','rental_price','deposit_amount'];

        $changes = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            if (in_array($key, $skip)) continue;
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;
            if ($oldVal === $newVal) continue;
            if (is_null($oldVal) && is_null($newVal)) continue;

            $changes[] = [
                'field'     => self::FIELD_LABELS[$key] ?? ucwords(str_replace('_', ' ', $key)),
                'field_key' => $key,
                'old'       => $this->formatValue($key, $oldVal),
                'new'       => $this->formatValue($key, $newVal),
                'is_money'  => in_array($key, $moneyFields),
            ];
        }
        return $changes;
    }

    private function formatValue(string $key, mixed $value): string
    {
        if (is_null($value)) return '-';
        $moneyFields = ['total_amount','paid_amount','late_fee','discount','rental_price','deposit_amount'];
        if (in_array($key, $moneyFields) && is_numeric($value)) {
            return 'Rp ' . number_format((float)$value, 0, ',', '.');
        }
        if (in_array($key, ['penalty_percent','max_penalty_percent']) && is_numeric($value)) {
            return number_format((float)$value, 0) . '%';
        }
        if (in_array($key, ['is_active','is_returned']) && in_array($value, [0,1,'0','1',true,false], true)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        return (string) $value;
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForBranch($query, ?int $branchId)
    {
        return $branchId ? $query->where('branch_id', $branchId) : $query;
    }

    public function scopeCritical($query)
    {
        return $query->whereIn('action', [
            'delete_rental','cancel_rental','delete_product',
            'delete_user','delete_customer','delete_package','update_rental',
        ]);
    }

    // ─── Static helper — dipanggil dari controller/service ───────────────────

    public static function record(
        string  $action,
        string  $description,
        ?object $model     = null,
        ?array  $oldValues = null,
        ?array  $newValues = null
    ): void {
        static::create([
            'user_id'     => auth()->id(),
            'branch_id'   => auth()->user()?->branch_id,
            'action'      => $action,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->id,
            'description' => $description,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
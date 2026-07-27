<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Laundry extends Model
{
    use SoftDeletes;

    protected $table = 'laundries';

    protected $fillable = [
        'transaksi_id',
        'produk_id',
        'status',
        'dikembalikan_at',
        'mulai_laundry_at',
        'selesai_laundry_at',
        'diproses_oleh',
        'catatan',
    ];

    protected $casts = [
        'dikembalikan_at'    => 'datetime',
        'mulai_laundry_at'   => 'datetime',
        'selesai_laundry_at' => 'datetime',
    ];

    // ─── Status Constants ─────────────────────────────────────────────────────

    const STATUS_MENUNGGU_LAUNDRY = 'menunggu_laundry';
    const STATUS_DALAM_LAUNDRY    = 'dalam_laundry';
    const STATUS_SIAP_DISEWAKAN   = 'siap_disewakan';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_MENUNGGU_LAUNDRY => 'Menunggu Laundry',
            self::STATUS_DALAM_LAUNDRY    => 'Dalam Laundry',
            self::STATUS_SIAP_DISEWAKAN   => 'Siap Disewakan',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_MENUNGGU_LAUNDRY => 'badge-warning',
            self::STATUS_DALAM_LAUNDRY    => 'badge-info',
            self::STATUS_SIAP_DISEWAKAN   => 'badge-success',
            default                       => 'badge-secondary',
        };
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeMenungguLaundry($query)
    {
        return $query->where('status', self::STATUS_MENUNGGU_LAUNDRY);
    }

    public function scopeDalamLaundry($query)
    {
        return $query->where('status', self::STATUS_DALAM_LAUNDRY);
    }

    public function scopeSiapDisewakan($query)
    {
        return $query->where('status', self::STATUS_SIAP_DISEWAKAN);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'transaksi_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produk_id');
    }

    public function diprosesByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'model');
    }

    // ─── Business Logic ───────────────────────────────────────────────────────

    /**
     * Mulai proses laundry: menunggu_laundry → dalam_laundry
     */
    public function mulaiLaundry(User $user, ?string $catatan = null): bool
    {
        if ($this->status !== self::STATUS_MENUNGGU_LAUNDRY) {
            return false;
        }

        $statusLama = $this->status;

        $this->update([
            'status'           => self::STATUS_DALAM_LAUNDRY,
            'mulai_laundry_at' => now(),
            'diproses_oleh'    => $user->id,
            'catatan'          => $catatan,
        ]);

        $this->catatHistory($statusLama, self::STATUS_DALAM_LAUNDRY, $user, $catatan);

        return true;
    }

    /**
     * Selesai laundry: dalam_laundry → siap_disewakan
     *
     * FIX #1: Tambah update products.status → 'available'
     *         agar isAvailable() kembali return true.
     */
    public function selesaiLaundry(User $user, ?string $catatan = null): bool
    {
        if ($this->status !== self::STATUS_DALAM_LAUNDRY) {
            return false;
        }

        $statusLama = $this->status;

        $this->update([
            'status'             => self::STATUS_SIAP_DISEWAKAN,
            'selesai_laundry_at' => now(),
            'diproses_oleh'      => $user->id,
            'catatan'            => $catatan,
        ]);

        // Kembalikan stok produk
        $this->produk->increment('stock_available');

        // FIX #1: Kembalikan status produk ke 'available'
        // Tanpa ini, isAvailable() tetap false karena status masih 'rented'
        if ($this->produk->status !== 'available') {
            $this->produk->update(['status' => 'available']);
        }

        $this->catatHistory($statusLama, self::STATUS_SIAP_DISEWAKAN, $user, $catatan);

        return true;
    }

    /**
     * Catat history perubahan status
     */
    private function catatHistory(string $statusLama, string $statusBaru, User $user, ?string $keterangan = null): void
    {
        StatusHistory::create([
            'model_type'  => self::class,
            'model_id'    => $this->id,
            'status_lama' => $statusLama,
            'status_baru' => $statusBaru,
            'keterangan'  => $keterangan,
            'user_id'     => $user->id,
            'changed_at'  => now(),
        ]);
    }
}
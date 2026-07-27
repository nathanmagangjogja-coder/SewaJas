<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalPackage extends Model
{
    protected $fillable = [
        'name', 'duration_days', 'description',
        'is_active', 'sort_order',
        'penalty_percent', 'max_penalty_percent',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'penalty_percent'     => 'decimal:2',
        'max_penalty_percent' => 'decimal:2',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'package_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getIsCustomAttribute(): bool
    {
        return $this->duration_days === 0;
    }

    public function getDurationLabelAttribute(): string
    {
        if ($this->is_custom) return 'Bebas (Custom)';
        return $this->duration_days . ' hari';
    }

    // ── PATCH: accessor untuk JSON serialization (dipakai Alpine.js di view) ──
    // Blade: @json($packages) akan otomatis menyertakan is_custom & duration_label
    public function getIsActiveStatusAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Non-aktif';
    }

    // ── PATCH: append accessor agar otomatis masuk saat ->toArray() / @json ───
    protected $appends = [
        'is_custom',
        'duration_label',
        'penalty_summary',
    ];

    // ─── Business Logic ───────────────────────────────────────────────────────

    /**
     * Hitung denda keterlambatan berdasarkan persentase dari subtotal.
     *
     * Formula:
     *   denda_per_hari = subtotal × (penalty_percent / 100)
     *   total_denda    = denda_per_hari × jumlah_hari_terlambat
     *   Jika max_penalty_percent diset → cap total_denda
     *
     * @param  float  $subtotal   Subtotal rental (sebelum diskon & denda)
     * @param  int    $lateDays   Jumlah hari keterlambatan
     * @return float  Total denda dalam Rupiah
     */
    public function calculatePenalty(float $subtotal, int $lateDays): float
    {
        if ($lateDays <= 0 || $subtotal <= 0) {
            return 0.0;
        }

        $penaltyPerDay = $subtotal * ($this->penalty_percent / 100);
        $totalPenalty  = $penaltyPerDay * $lateDays;

        // Terapkan cap jika ada
        if ($this->max_penalty_percent !== null) {
            $maxPenalty   = $subtotal * ($this->max_penalty_percent / 100);
            $totalPenalty = min($totalPenalty, $maxPenalty);
        }

        return round($totalPenalty, 2);
    }

    /**
     * Ringkasan formula denda untuk ditampilkan di UI.
     * Contoh: "10% / hari (maks. 100%)"
     */
    public function getPenaltySummaryAttribute(): string
    {
        $str = number_format($this->penalty_percent, 0) . '% per hari';
        if ($this->max_penalty_percent !== null) {
            $str .= ' (maks. ' . number_format($this->max_penalty_percent, 0) . '%)';
        } else {
            $str .= ' (tanpa batas)';
        }
        return $str;
    }
}

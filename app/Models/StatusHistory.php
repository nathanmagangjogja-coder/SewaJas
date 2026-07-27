<?php

namespace App\Models;
use App\Models\Laundry;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatusHistory extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'status_lama',
        'status_baru',
        'keterangan',
        'user_id',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function getStatusLamaLabelAttribute(): string
    {
        return $this->formatStatusLabel($this->status_lama);
    }

    public function getStatusBaruLabelAttribute(): string
    {
        return $this->formatStatusLabel($this->status_baru);
    }

    private function formatStatusLabel(?string $status): string
    {
        if (!$status) return '-';

        $labels = array_merge(
            Laundry::statusLabels(),
           
            Rental::statusLabels()
        );

        return $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    }
}

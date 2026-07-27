<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalReturn extends Model
{
    protected $fillable = [
        'rental_id',
        'returned_at',
        'late_days',
        'late_fee',
        'condition',
        'return_notes',
    ];

    protected $casts = [
        'returned_at' => 'date',
        'late_days'   => 'integer',
        'late_fee'    => 'decimal:2',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function getConditionLabelAttribute(): string
    {
        return match($this->condition) {
            'baik'         => 'Baik',
            'kurang_baik'  => 'Kurang Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat'  => 'Rusak Berat',
            default        => $this->condition,
        };
    }

    public function getConditionBadgeColorAttribute(): string
    {
        return match($this->condition) {
            'baik'         => 'green',
            'kurang_baik'  => 'yellow',
            'rusak_ringan' => 'orange',
            'rusak_berat'  => 'red',
            default        => 'gray',
        };
    }
}
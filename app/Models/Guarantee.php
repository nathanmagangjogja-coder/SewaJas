<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guarantee extends Model
{
    protected $fillable = [
        'rental_id', 'type', 'id_number', 'id_name',
        'deposit_amount', 'description', 'photo', 'status', 'notes', 'returned_at',
    ];

    protected $casts = [
        'deposit_amount' => 'decimal:2',
        'returned_at' => 'datetime',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'ktp'     => 'KTP',
            'sim'     => 'SIM',
            'deposit' => 'Deposit Uang',
            'custom'  => 'Jaminan Custom',
            default   => $this->type,
        };
    }
}

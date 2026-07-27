<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'rental_id', 'received_by', 'payment_number', 'amount',
        'method', 'payment_channel', 'account_number', 'reference_number',
        'type', 'notes', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'cash'     => 'Tunai',
            'transfer' => 'Transfer',
            'qris'     => 'QRIS',
            'other'    => 'Lainnya',
            default    => $this->method,
        };
    }

        public function getMaskedAccountNumberAttribute(): ?string
    {
        if (!$this->account_number) {
            return null;
        }

        $digits = preg_replace('/\s+/', '', $this->account_number);
        $len    = strlen($digits);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . substr($digits, -4);
    }

        public function getChannelLabelAttribute(): ?string
    {
        if (!$this->payment_channel) {
            return null;
        }

        if ($this->method === 'transfer' && $this->account_number) {
            return "{$this->payment_channel} · {$this->masked_account_number}";
        }

        return $this->payment_channel;
    }
}
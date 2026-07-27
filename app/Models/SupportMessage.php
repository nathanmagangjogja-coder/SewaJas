<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'user_id', 'branch_id', 'type', 'subject', 'message',
        'status', 'read_at', 'read_by',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    const TYPE_BUG     = 'bug_report';
    const TYPE_CONTACT = 'contact_admin';
    const TYPE_OTHER   = 'other';

    public static function typeLabels(): array
    {
        return [
            self::TYPE_BUG     => 'Laporan Bug',
            self::TYPE_CONTACT => 'Hubungi Admin',
            self::TYPE_OTHER   => 'Lainnya',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function readBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }
}

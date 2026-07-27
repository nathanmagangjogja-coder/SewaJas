<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'send_at_times',
        'message_templates',
        'is_active',
        'target_audience',
        'cooldown_hours',
    ];

    protected $casts = [
        'send_at_times' => 'array',
        'message_templates' => 'array',
        'is_active' => 'boolean',
        'cooldown_hours' => 'integer',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(BroadcastLog::class);
    }
}

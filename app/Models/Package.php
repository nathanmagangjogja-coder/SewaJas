<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration_days',
        'sort_order',
        'penalty_percent',
        'max_penalty_percent',
        'is_custom',
        'is_active',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
        'penalty_percent' => 'decimal:2',
        'max_penalty_percent' => 'decimal:2',
    ];
}
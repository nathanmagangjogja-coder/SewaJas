<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'name', 'phone', 'address',
        'id_number', 'photo', 'id_photo', 'id_photo_type', 'id_photo_reusable_until',
        'chest', 'waist', 'hip', 'height', 'weight',
        'suit_size', 'shirt_size', 'trouser_size', 'shoe_size',
        'body_notes', 'notes', 'is_blacklisted', 'blacklist_reason',
    ];

    protected $casts = [
        'is_blacklisted' => 'boolean',
        'id_photo_reusable_until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Customer $customer) {
            $customer->name = ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $customer->name))));

            $phone = preg_replace('/[^0-9]/', '', $customer->phone);
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }
            $customer->phone = $phone;
        });
    }

    public static function findDuplicate(string $name, string $phone): ?self
    {
        $normName = strtolower(trim(preg_replace('/\s+/', ' ', $name)));
        $normPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($normPhone, '0')) {
            $normPhone = '62' . substr($normPhone, 1);
        }

        return self::whereRaw('LOWER(TRIM(name)) = ?', [$normName])
            ->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$normPhone])
            ->first();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function getPhotoUrlAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name='
            . urlencode($this->name)
            . '&background=E8DED1'
            . '&color=2B2B2B'
            . '&size=128'
            . '&bold=true';
    }

    public function getIsIdPhotoReusableAttribute(): bool
    {
        if (!$this->id_photo) {
            return false;
        }

        return $this->id_photo_reusable_until === null
            || $this->id_photo_reusable_until->isFuture();
    }

    public function getTotalRentalsAttribute(): int
    {
        return $this->rentals()->count();
    }

    public function getActiveRentalsAttribute()
    {
        return $this->rentals()->whereIn('rental_status', ['active', 'overdue'])->get();
    }
}
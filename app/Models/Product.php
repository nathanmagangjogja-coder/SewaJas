<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'category_id', 'code', 'name', 'description',
        'size', 'color', 'brand', 'rental_price', 'deposit_price',
        'stock_total', 'stock_available', 'photo', 'qr_code',
        'condition', 'status', 'notes',
    ];

    protected $casts = [
        'rental_price' => 'decimal:2',
        'deposit_price' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return asset('images/no-product.png');
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        if ($this->qr_code) {
            return asset('storage/' . $this->qr_code);
        }
        return null;
    }

    public function isAvailable(): bool
    {
        return $this->stock_available > 0 && $this->status === 'available';
    }

    public function getRentalPriceFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->rental_price, 0, ',', '.');
    }

    /**
     * BARU (refactor): sama seperti Rental::scopeForBranch — mengganti pola
     * berulang `when($branchId, fn($q) => $q->where('branch_id', $branchId))`
     * yang sebelumnya ditulis manual di ReportController::stock().
     */
    public function scopeForBranch($query, ?int $branchId)
    {
        return $branchId !== null ? $query->where('branch_id', $branchId) : $query;
    }
}
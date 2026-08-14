<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
        'phone',
        'avatar',
        'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ─── Roles Constants ─────────────────────────────────────────────────────

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN_TOKO  = 'admin_toko';
    const ROLE_SALES       = 'sales';

    const ROLES = [
        self::ROLE_SUPER_ADMIN => 'Super Admin',
        self::ROLE_ADMIN_TOKO  => 'Admin Toko',
        self::ROLE_SALES       => 'Sales',
    ];

    // ─── Role Helpers ────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdminToko(): bool
    {
        return $this->role === self::ROLE_ADMIN_TOKO;
    }

    public function isSales(): bool
    {
        return $this->role === self::ROLE_SALES;
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    /**
     * FIX: method ini dipanggil oleh App\Http\Middleware\EnsureBranchAccess
     * tapi sebelumnya tidak pernah didefinisikan → akan fatal error
     * (Call to undefined method) begitu middleware 'branch.access' dipakai
     * di sebuah route. Ditambahkan agar middleware tsb aman digunakan.
     */
    public function canAccessBranch(int $branchId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->branch_id === $branchId;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getRoleBadgeColorAttribute(): string
    {
        return match($this->role) {
            self::ROLE_SUPER_ADMIN => 'bg-amber-100 text-amber-800',
            self::ROLE_ADMIN_TOKO  => 'bg-blue-100 text-blue-800',
            self::ROLE_SALES       => 'bg-green-100 text-green-800',
            default                => 'bg-gray-100 text-gray-800',
        };
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function rentals()
    {
        return $this->hasMany(Rental::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function notifications()
{
    return $this->hasMany(\App\Models\Notification::class);
}

public function getAvatarUrlAttribute(): string
{
    return $this->avatar
        ? asset('storage/' . $this->avatar)
        : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=D6B98C&color=fff&size=128';
}
}
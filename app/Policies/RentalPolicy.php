<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Rental;

class RentalPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // semua role bisa lihat list rental
    }

    public function view(User $user, Rental $rental): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->branch_id === $rental->branch_id;
    }

    public function create(User $user): bool
    {
        return true; // semua role bisa buat rental
    }

    public function update(User $user, Rental $rental): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->branch_id === $rental->branch_id;
    }

    public function processPayment(User $user, Rental $rental): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->branch_id === $rental->branch_id;
    }

    public function processReturn(User $user, Rental $rental): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->branch_id === $rental->branch_id;
    }

    public function cancel(User $user, Rental $rental): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->role === 'sales') return false; // sales tidak bisa cancel
        return $user->branch_id === $rental->branch_id;
    }

    public function delete(User $user, Rental $rental): bool
    {
        return $user->isSuperAdmin();
    }
}
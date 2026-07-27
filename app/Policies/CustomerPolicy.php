<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // semua role bisa lihat list customer
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->branch_id === $customer->branch_id;
    }

    public function create(User $user): bool
    {
        return true; // semua role bisa tambah customer
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->role === 'sales') return false; // sales tidak bisa edit
        return $user->branch_id === $customer->branch_id;
    }

    public function blacklist(User $user, Customer $customer): bool
    {
        if ($user->isSuperAdmin()) return true;
        if ($user->role === 'sales') return false;
        return $user->branch_id === $customer->branch_id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->isSuperAdmin();
    }
}
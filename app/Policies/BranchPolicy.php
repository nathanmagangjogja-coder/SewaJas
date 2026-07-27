<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Branch;

class BranchPolicy
{
    public function viewAny(User $user): bool         { return $user->isSuperAdmin(); }
    public function view(User $user, Branch $branch): bool   { return $user->isSuperAdmin(); }
    public function create(User $user): bool          { return $user->isSuperAdmin(); }
    public function update(User $user, Branch $branch): bool { return $user->isSuperAdmin(); }
    public function delete(User $user, Branch $branch): bool { return $user->isSuperAdmin(); }
}

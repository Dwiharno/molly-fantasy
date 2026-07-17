<?php

namespace App\Policies;

use App\Models\StockOpname;
use App\Models\User;

class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockOpname $opname): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, StockOpname $opname): bool
    {
        return $user->canWrite() && $opname->status !== 'completed';
    }

    public function delete(User $user, StockOpname $opname): bool
    {
        return $user->hasRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]) && $opname->status !== 'completed';
    }
}

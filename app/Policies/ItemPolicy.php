<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // semua role yang login boleh melihat daftar item
    }

    public function view(User $user, Item $item): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_STAFF]);
    }

    public function update(User $user, Item $item): bool
    {
        return $user->hasRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_STAFF]);
    }

    public function delete(User $user, Item $item): bool
    {
        // Hanya Super Admin & Admin yang boleh menghapus master item
        return $user->hasRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);
    }

    public function import(User $user): bool
    {
        return $user->hasRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);
    }
}

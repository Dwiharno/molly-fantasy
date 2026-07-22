<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function view(User $user, User $target): bool
    {
        return $user->canManageUsers()
            && ($user->isSuperAdmin() || $user->store_id === $target->store_id);
    }

    public function create(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->canManageUsers()) {
            return false;
        }

        // Admin biasa tidak boleh mengubah akun Super Admin.
        if ($target->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->isSuperAdmin() || $user->store_id === $target->store_id;
    }

    public function delete(User $user, User $target): bool
    {
        if ($target->is($user)) {
            return false; // tidak boleh menghapus akun sendiri
        }

        if ($target->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->canManageUsers()
            && ($user->isSuperAdmin() || $user->store_id === $target->store_id);
    }

    public function resetPassword(User $user, User $target): bool
    {
        if ($target->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->canManageUsers()
            && ($user->isSuperAdmin() || $user->store_id === $target->store_id);
    }
}

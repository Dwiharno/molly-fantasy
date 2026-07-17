<?php

namespace App\Services;

use App\Jobs\SyncToGoogleSheetsJob;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(protected ActivityLogService $activityLog)
    {
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->activityLog->log(
                Auth::user(), 'create', 'master_user',
                "Menambahkan user baru: {$user->name} ({$user->email}) dengan role {$user->role}"
            );

            SyncToGoogleSheetsJob::dispatch('user', [
                $user->name, $user->email, $user->role,
                $user->is_active ? 'Aktif' : 'Nonaktif', now()->format('Y-m-d H:i:s'),
            ]);

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? $user->is_active,
            ]);

            $this->activityLog->log(
                Auth::user(), 'update', 'master_user',
                "Mengubah data user: {$user->name} ({$user->email})"
            );

            return $user->fresh();
        });
    }

    public function delete(User $user): bool
    {
        $name = $user->name;
        $result = (bool) $user->delete();

        if ($result) {
            $this->activityLog->log(Auth::user(), 'delete', 'master_user', "Menghapus user: {$name}");
        }

        return $result;
    }

    public function resetPassword(User $user, string $newPassword): void
    {
        $user->forceFill(['password' => Hash::make($newPassword)])->save();

        $this->activityLog->log(
            Auth::user(), 'reset_password', 'master_user',
            "Mereset password untuk user: {$user->name} ({$user->email})"
        );
    }

    public function toggleActive(User $user): User
    {
        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'mengaktifkan' : 'menonaktifkan';
        $this->activityLog->log(
            Auth::user(), 'update', 'master_user',
            ucfirst($status)." user: {$user->name}"
        );

        return $user->fresh();
    }
}

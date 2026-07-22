<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\ResetUserPasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Models\Store;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller implements HasMiddleware
{
    public function __construct(protected UserService $userService)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,App\Models\User', only: ['index', 'data']),
            new Middleware('can:create,App\Models\User', only: ['create', 'store']),
            new Middleware('can:update,user', only: ['edit', 'update']),
            new Middleware('can:delete,user', only: ['destroy']),
            new Middleware('can:resetPassword,user', only: ['resetPasswordForm', 'resetPassword']),
        ];
    }

    public function index(): View
    {
        return view('users.index', [
            'roles' => User::ROLES,
        ]);
    }

    public function data(): JsonResponse
    {
        $query = User::query()->with('store');

        // Admin biasa tidak melihat akun Super Admin di listing untuk menjaga hierarki akses.
        if (! auth()->user()->isSuperAdmin()) {
            $query->where('role', '!=', User::ROLE_SUPER_ADMIN)
                ->where('store_id', auth()->user()->store_id);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('role_label', fn (User $u) => User::ROLES[$u->role] ?? $u->role)
            ->addColumn('store_label', fn (User $u) => $u->store ? "{$u->store->code} - {$u->store->name}" : '-')
            ->addColumn('status_badge', fn (User $u) => $u->is_active
                ? '<span class="badge text-bg-success">Aktif</span>'
                : '<span class="badge text-bg-secondary">Nonaktif</span>')
            ->addColumn('last_login', fn (User $u) => $u->last_login_at?->diffForHumans() ?? 'Belum pernah login')
            ->addColumn('actions', function (User $u) {
                return view('users._actions', ['user' => $u])->render();
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => new User(),
            'roles' => $this->availableRoles(),
            'stores' => $this->availableStores(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.form', [
            'user' => $user,
            'roles' => $this->availableRoles(),
            'stores' => $this->availableStores(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): JsonResponse
    {
        $this->userService->resetPassword($user, $request->validated()['password']);

        return response()->json(['message' => "Password untuk {$user->name} berhasil direset."]);
    }

    public function toggleActive(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->userService->toggleActive($user);

        return response()->json([
            'message' => 'Status user berhasil diubah.',
            'is_active' => $user->is_active,
        ]);
    }

    protected function availableRoles(): array
    {
        $allowed = [User::ROLE_ADMIN, User::ROLE_STAFF];
        if (auth()->user()->isSuperAdmin()) {
            array_unshift($allowed, User::ROLE_SUPER_ADMIN);
        }
        $roles = array_intersect_key(User::ROLES, array_flip($allowed));

        return $roles;
    }

    protected function availableStores()
    {
        return Store::active()
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->whereKey(auth()->user()->store_id))
            ->orderBy('code')->get();
    }
}

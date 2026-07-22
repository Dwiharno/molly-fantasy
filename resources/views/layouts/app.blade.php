<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ \App\Models\Setting::get('outlet_name', config('app.name')) }}</title>

    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div class="app-wrapper d-flex">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="brand-mark">mollyfantasy</span>
                <small>モーリーファンタジー</small>
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
                </a>
                <a href="{{ route('items.index') }}" class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-box"></i><span>Master Item</span>
                </a>
                <a href="{{ route('stores.index') }}" class="nav-link {{ request()->routeIs('stores.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-store"></i><span>Master Outlet</span>
                </a>
                @if(auth()->user()?->canManageUsers())
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i><span>Master User</span>
                </a>
                @endif
                <a href="{{ route('stock-opname.index') }}" class="nav-link {{ request()->routeIs('stock-opname.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-check"></i><span>Stock Opname</span>
                </a>
                <a href="{{ route('redeem.index') }}" class="nav-link {{ request()->routeIs('redeem.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-ticket"></i><span>Redeem Hadiah</span>
                </a>
                <a href="{{ route('laporan.index') }}" class="nav-link {{ request()->routeIs('laporan.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line"></i><span>Laporan</span>
                </a>
                <a href="{{ route('activity-logs.index') }}" class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check"></i><span>Log Aktivitas</span>
                </a>
                @if(auth()->user()?->isSuperAdmin())
                <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-gear"></i><span>Setting</span>
                </a>
                @endif
            </nav>
        </aside>

        <div class="main-content flex-grow-1">
            <nav class="topbar d-flex align-items-center justify-content-between">
                <button id="sidebarToggle" class="btn btn-sm btn-icon" aria-label="Buka atau tutup menu" aria-controls="sidebar" aria-expanded="true"><i class="fa-solid fa-bars"></i></button>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('redeem.offline') }}" id="networkModeToggle" class="btn btn-sm btn-outline-success position-relative" title="Buka Redeem Offline">
                        <i class="fa-solid fa-wifi me-1" id="networkModeIcon"></i>
                        <span id="networkModeText">Online</span>
                        <span id="offlineQueueBadge" class="badge rounded-pill text-bg-danger position-absolute top-0 start-100 translate-middle d-none">0</span>
                    </a>
                    <button id="darkModeToggle" class="btn btn-sm btn-icon"><i class="fa-solid fa-moon"></i></button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon position-relative" data-bs-toggle="dropdown" id="notifBell">
                            <i class="fa-solid fa-bell"></i>
                            <span class="badge rounded-pill text-bg-danger position-absolute top-0 start-100 translate-middle d-none" id="notifBadge">0</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0" style="width: 320px;" id="notifDropdown">
                            <div class="p-2 border-bottom d-flex justify-content-between align-items-center">
                                <strong class="small">Notifikasi</strong>
                                <a href="{{ route('notifications.index') }}" class="small">Lihat Semua</a>
                            </div>
                            <div id="notifList" style="max-height: 320px; overflow-y: auto;">
                                <div class="p-3 text-center text-muted small">Memuat...</div>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle fs-5"></i>
                            <span>{{ auth()->user()?->name }}</span>
                            <span class="badge text-bg-secondary">{{ \App\Models\User::ROLES[auth()->user()?->role] ?? '' }}</span>
                            @if(auth()->user()?->store)
                                <span class="badge text-bg-success">{{ auth()->user()->store->code }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="page-content">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables.net/js/dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('vendor/chart.js/chart.umd.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        window.MollyOffline = {
            queueKey: 'molly_redeem_offline_queue_v1',
            modeKey: 'molly_redeem_force_offline_v1',
            queue() {
                try { return JSON.parse(localStorage.getItem(this.queueKey) || '[]'); }
                catch (_) { return []; }
            },
            forced() { return localStorage.getItem(this.modeKey) === '1'; },
            isOffline() { return this.forced() || !navigator.onLine; },
            render() {
                const offline = this.isOffline();
                const count = this.queue().length;
                const button = document.getElementById('networkModeToggle');
                const icon = document.getElementById('networkModeIcon');
                const label = document.getElementById('networkModeText');
                const badge = document.getElementById('offlineQueueBadge');
                if (!button || !icon || !label || !badge) return;
                button.className = 'btn btn-sm position-relative ' + (offline ? 'btn-warning' : 'btn-outline-success');
                icon.className = 'fa-solid ' + (offline ? 'fa-cloud-arrow-up' : 'fa-wifi') + ' me-1';
                label.textContent = offline ? 'Offline' : 'Online';
                badge.textContent = count;
                badge.classList.toggle('d-none', count === 0);
                button.title = count ? `${count} transaksi menunggu sinkronisasi` : 'Buka Redeem Offline';
            }
        };
        window.addEventListener('online', () => {
            window.MollyOffline.render();
            window.dispatchEvent(new CustomEvent('molly:sync-offline'));
        });
        window.addEventListener('offline', () => window.MollyOffline.render());
        window.addEventListener('storage', () => window.MollyOffline.render());
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) window.MollyOffline.render();
        });
        window.MollyOffline.render();

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}'));
        }
    </script>
    @stack('scripts')
</body>
</html>

@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Dashboard</h4>
    <span class="text-muted small">{{ now()->translatedFormat('l, d F Y') }}</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div>
                <div class="stat-value">Rp {{ number_format($stats['total_inventory_value'], 0, ',', '.') }}</div>
                <div class="stat-label">Total Value Inventory (Harga × Stok)</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-gift"></i></div>
            <div>
                <div class="stat-value">Rp {{ number_format($stats['total_redeem_value'], 0, ',', '.') }}</div>
                <div class="stat-label">Total Value Redeem (Harga × Qty)</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon bg-dark-subtle text-dark"><i class="fa-solid fa-box-open"></i></div>
            <div>
                <div class="stat-value">{{ number_format($stats['out_of_stock']) }}</div>
                <div class="stat-label">Stock Kosong</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-box"></i></div>
            <div>
                <div class="stat-value">{{ number_format($stats['total_item']) }}</div>
                <div class="stat-label">Jumlah Item</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-ticket"></i></div>
            <div>
                <div class="stat-value">{{ number_format($stats['redeem_today']) }}</div>
                <div class="stat-label">Redeem Hari Ini</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-clipboard-check"></i></div>
            <div>
                <div class="stat-value">{{ number_format($stats['opname_today']) }}</div>
                <div class="stat-label">Stock Opname Hari Ini</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info-subtle text-info"><i class="fa-solid fa-barcode"></i></div>
            <div>
                <div class="stat-value">{{ number_format($stats['ticket_redeemed_today']) }}</div>
                <div class="stat-label">Tiket Diredeem Hari Ini</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Grafik Redeem (14 Hari Terakhir)</div>
            <div class="card-body"><canvas id="chartRedeem" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Grafik Stock Opname (14 Hari Terakhir)</div>
            <div class="card-body"><canvas id="chartOpname" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Grafik Barang Masuk</div>
            <div class="card-body"><canvas id="chartMasuk" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Grafik Barang Keluar</div>
            <div class="card-body"><canvas id="chartKeluar" height="120"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Top 10 Hadiah</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Nama Hadiah</th><th class="text-end">Qty Diredeem</th></tr></thead>
                    <tbody>
                        @forelse($topHadiah as $row)
                            <tr><td>{{ $row->item_name }}</td><td class="text-end">{{ $row->total_qty }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">Belum ada data redeem.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Aktivitas Terbaru</div>
            <ul class="list-group list-group-flush">
                @forelse($recentActivities as $activity)
                    <li class="list-group-item small">
                        <strong>{{ $activity->user->name ?? 'System' }}</strong> — {{ $activity->description }}
                        <div class="text-muted">{{ $activity->created_at->diffForHumans() }}</div>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted py-4">Belum ada aktivitas.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const chartData = {
        redeem: @json($redeemChart),
        opname: @json($opnameChart),
        masuk: @json($barangMasukChart),
        keluar: @json($barangKeluarChart),
    };

    function buildChart(canvasId, dataset, label, color) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dataset.map(d => d.tanggal),
                datasets: [{
                    label: label,
                    data: dataset.map(d => d.total),
                    borderColor: color,
                    backgroundColor: color + '33',
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

    buildChart('chartRedeem', chartData.redeem, 'Redeem', '#0d6efd');
    buildChart('chartOpname', chartData.opname, 'Stock Opname', '#ffc107');
    buildChart('chartMasuk', chartData.masuk, 'Barang Masuk', '#198754');
    buildChart('chartKeluar', chartData.keluar, 'Barang Keluar', '#dc3545');
</script>
@endpush

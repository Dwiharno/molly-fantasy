@extends('layouts.app')

@section('title', 'Detail Stock Opname - ' . $opname->code)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0">Detail Stock Opname — {{ $opname->code }}</h4>
        <span class="text-muted small">
            {{ $opname->opname_date->translatedFormat('d F Y') }} • Petugas: {{ $opname->user->name }}
        </span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('stock-opname.berita-acara', $opname) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-file-signature me-1"></i> Generate Berita Acara
        </a>
        <a href="{{ route('stock-opname.export.pdf', $opname) }}" class="btn btn-outline-danger btn-sm">
            <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
        </a>
        <a href="{{ route('stock-opname.export.excel', $opname) }}" class="btn btn-outline-success btn-sm">
            <i class="fa-solid fa-file-excel me-1"></i> Export Excel
        </a>
        <a href="{{ route('stock-opname.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

@php
    $totalSelisihPlus = $opname->details->where('difference', '>', 0)->count();
    $totalSelisihMinus = $opname->details->where('difference', '<', 0)->count();
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card"><div><div class="stat-value">{{ $opname->details->count() }}</div><div class="stat-label">Total Item Dihitung</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div><div class="stat-value text-success">{{ $totalSelisihPlus }}</div><div class="stat-label">Item Selisih Lebih</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div><div class="stat-value text-danger">{{ $totalSelisihMinus }}</div><div class="stat-label">Item Selisih Kurang</div></div></div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Barcode</th><th>Nama Item</th>
                    <th class="text-center">Expected Stock</th><th class="text-center">Actual Stock</th><th class="text-center">Selisih</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opname->details as $detail)
                    <tr>
                        <td>{{ $detail->item->barcode ?? '-' }}</td>
                        <td>{{ $detail->item->name ?? '(item dihapus)' }}</td>
                        <td class="text-center">{{ $detail->expected_stock }}</td>
                        <td class="text-center">{{ $detail->actual_stock }}</td>
                        <td class="text-center">
                            @if($detail->difference > 0)
                                <span class="badge text-bg-success">+{{ $detail->difference }}</span>
                            @elseif($detail->difference < 0)
                                <span class="badge text-bg-danger">{{ $detail->difference }}</span>
                            @else
                                <span class="badge text-bg-secondary">0</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

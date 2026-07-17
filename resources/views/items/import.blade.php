@extends('layouts.app')

@section('title', 'Import Master Item')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Import Master Item</h4>
    <a href="{{ route('items.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted">
            Format kolom Excel/CSV: <code>barcode, nama, allocation, kategori, sub_kategori, harga, tiket, qty, status</code>.<br>
            Nilai <code>allocation</code>, <code>kategori</code>, dan <code>sub_kategori</code> harus persis sama dengan pilihan
            yang ada di form Master Item — lihat tombol Download Template di bawah untuk contoh nilainya.
            Barcode yang sudah terdaftar akan dilewati otomatis.
        </p>

        <a href="{{ route('items.import.template') }}" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="fa-solid fa-download me-1"></i> Download Template
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @error('file')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <form action="{{ route('items.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label">File Excel / CSV <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-file-import me-1"></i> Import Sekarang
            </button>
        </form>
    </div>
</div>
@endsection

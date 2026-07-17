@extends('layouts.app')

@section('title', $item->exists ? 'Edit Item' : 'Tambah Item')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ $item->exists ? 'Edit Item' : 'Tambah Item Baru' }}</h4>
    <a href="{{ route('items.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $item->exists ? route('items.update', $item) : route('items.store') }}" method="POST">
            @csrf
            @if($item->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Barcode <span class="text-danger">*</span></label>
                    <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror"
                           value="{{ old('barcode', $item->barcode) }}" required autofocus>
                    @error('barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Item <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $item->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Allocation <span class="text-danger">*</span></label>
                    <select name="allocation" class="form-select select2 @error('allocation') is-invalid @enderror" required>
                        <option value="">- Pilih Allocation -</option>
                        @foreach($allocations as $alloc)
                            <option value="{{ $alloc }}" {{ old('allocation', $item->allocation) === $alloc ? 'selected' : '' }}>
                                {{ $alloc }}
                            </option>
                        @endforeach
                    </select>
                    @error('allocation') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select select2 @error('category') is-invalid @enderror" required>
                        <option value="">- Pilih Category -</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category', $item->category) === $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                        @endforeach
                    </select>
                    @error('category') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sub Category <span class="text-danger">*</span></label>
                    <select name="sub_category" class="form-select select2 @error('sub_category') is-invalid @enderror" required>
                        <option value="">- Pilih Sub Category -</option>
                        @foreach($subCategories as $sub)
                            <option value="{{ $sub }}" {{ old('sub_category', $item->sub_category) === $sub ? 'selected' : '' }}>
                                {{ $sub }}
                            </option>
                        @endforeach
                    </select>
                    @error('sub_category') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Price <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" step="0.01" name="selling_price" class="form-control @error('selling_price') is-invalid @enderror"
                               value="{{ old('selling_price', $item->selling_price) }}" required>
                        @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nilai Tiket <span class="text-danger">*</span></label>
                    <input type="number" name="ticket_redeem_qty" class="form-control @error('ticket_redeem_qty') is-invalid @enderror"
                           value="{{ old('ticket_redeem_qty', $item->ticket_redeem_qty ?: 1) }}" min="1" required>
                    @error('ticket_redeem_qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Jumlah tiket yang dibutuhkan untuk redeem 1 item ini.</div>
                </div>
            </div>

            @if($item->exists)
                <div class="alert alert-info mt-4 small mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Stok saat ini: <strong>{{ $item->stock }}</strong> — stok hanya berubah otomatis lewat menu
                    <strong>Stock Opname</strong> (hitung ulang) atau <strong>Redeem Hadiah</strong> (berkurang saat diredeem),
                    tidak diketik manual di sini.
                </div>
            @endif

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-1"></i> Simpan
                </button>
                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $('.select2').select2({ theme: 'default', width: '100%' });
</script>
@endpush

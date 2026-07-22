@extends('layouts.app')

@section('title', $user->exists ? 'Edit User' : 'Tambah User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ $user->exists ? 'Edit User' : 'Tambah User Baru' }}</h4>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" method="POST">
            @csrf
            @if($user->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Staff <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}" required autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email) }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if(!$user->exists)
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="8" required>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                </div>
                @endif

                <div class="col-md-6">
                    <label class="form-label">Role / Hak Akses <span class="text-danger">*</span></label>
                    <select name="role" class="form-select select2 @error('role') is-invalid @enderror" required>
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ old('role', $user->role) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Master Outlet <span class="text-danger">*</span></label>
                    <select name="store_id" class="form-select select2 @error('store_id') is-invalid @enderror" required>
                        <option value="">- Pilih Outlet -</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ (string) old('store_id', $user->store_id) === (string) $store->id ? 'selected' : '' }}>
                                {{ $store->code }} - {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('store_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive"
                               {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Status Aktif</label>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-3 small">
                <strong>Hak akses per role:</strong><br>
                Super Admin — seluruh modul termasuk Setting sistem.<br>
                Store Manager/Leader — mengelola user dan operasional hanya untuk outletnya.<br>
                Staff — input transaksi (Redeem, Stock Opname), tidak bisa menghapus master data.<br>
                Viewer — hanya bisa melihat data (read-only) di seluruh modul.
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Simpan</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Batal</a>
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

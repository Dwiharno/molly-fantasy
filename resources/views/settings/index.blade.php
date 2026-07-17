@extends('layouts.app')

@section('title', 'Setting')

@section('content')
<h4 class="mb-4">Setting</h4>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Informasi Outlet</div>
            <div class="card-body">
                <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama Outlet <span class="text-danger">*</span></label>
                        <input type="text" name="outlet_name" class="form-control" value="{{ old('outlet_name', $settings['outlet_name']) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="outlet_address" class="form-control" rows="2">{{ old('outlet_address', $settings['outlet_address']) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jam Operasional</label>
                        <input type="text" name="operational_hours" class="form-control" placeholder="Contoh: 10:00 - 22:00"
                               value="{{ old('operational_hours', $settings['operational_hours']) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo Outlet</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        @if($settings['outlet_logo'])
                            <div class="mt-2">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings['outlet_logo']) }}"
                                     class="rounded" width="60" height="60" style="object-fit:cover">
                            </div>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Simpan Pengaturan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">Backup Database</div>
            <div class="card-body">
                <p class="text-muted small">Unduh salinan lengkap database saat ini dalam format .sql.</p>
                <a href="{{ route('settings.backup') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-download me-1"></i> Backup Sekarang
                </a>

                <hr>
                <p class="small fw-semibold mb-2">Riwayat Backup</p>
                <ul class="list-group list-group-flush">
                    @forelse($backups as $b)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="small">{{ $b['name'] }}</div>
                                <div class="text-muted" style="font-size:.75rem">{{ $b['modified']->format('d/m/Y H:i') }} • {{ round($b['size']/1024, 1) }} KB</div>
                            </div>
                            <a href="{{ route('settings.backup.download', $b['name']) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-download"></i>
                            </a>
                        </li>
                    @empty
                        <li class="list-group-item px-0 text-muted small">Belum ada backup.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Restore Database</div>
            <div class="card-body">
                <div class="alert alert-warning small">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Restore akan menimpa seluruh data yang ada saat ini. Pastikan Anda sudah membackup data terbaru sebelum melanjutkan.
                </div>
                <form action="{{ route('settings.restore') }}" method="POST" enctype="multipart/form-data" id="formRestore">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">File Backup (.sql)</label>
                        <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                    </div>
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fa-solid fa-upload me-1"></i> Restore Database
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#formRestore').on('submit', function (e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Yakin ingin restore database?',
            text: 'Seluruh data saat ini akan ditimpa oleh isi file backup.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Restore',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endpush

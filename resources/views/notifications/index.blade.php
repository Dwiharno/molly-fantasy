@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Notifikasi</h4>
    <button type="button" id="btnMarkAllRead" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-check-double me-1"></i> Tandai Semua Dibaca
    </button>
</div>

<div class="card">
    <ul class="list-group list-group-flush">
        @forelse($notifications as $n)
            <li class="list-group-item d-flex gap-3 {{ $n->read_at ? '' : 'bg-primary-subtle' }}">
                <div class="stat-icon bg-{{ $n->data['color'] ?? 'secondary' }}-subtle text-{{ $n->data['color'] ?? 'secondary' }}">
                    <i class="fa-solid {{ $n->data['icon'] ?? 'fa-bell' }}"></i>
                </div>
                <div>
                    <div class="fw-semibold">{{ $n->data['title'] ?? '-' }}</div>
                    <div class="small text-muted">{{ $n->data['message'] ?? '-' }}</div>
                    <div class="small text-muted">{{ $n->created_at->diffForHumans() }}</div>
                </div>
            </li>
        @empty
            <li class="list-group-item text-center text-muted py-5">Belum ada notifikasi.</li>
        @endforelse
    </ul>
</div>

<div class="mt-3">
    {{ $notifications->links() }}
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#btnMarkAllRead').on('click', function () {
        $.ajax({
            url: '{{ route('notifications.mark-all-read') }}',
            method: 'POST',
            success: function (res) { mfToast('success', res.message); location.reload(); }
        });
    });
});
</script>
@endpush

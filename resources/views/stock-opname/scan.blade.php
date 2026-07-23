@extends('layouts.app')

@section('title', 'Input Stock Opname - ' . $opname->code)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0">Input Stock Opname — {{ $opname->code }}</h4>
        <span class="text-muted small">{{ $opname->opname_date->translatedFormat('d F Y') }} · Isi actual stock setiap item tanpa scan barcode.</span>
    </div>
    <a href="{{ route('stock-opname.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Riwayat
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card"><div><div class="stat-value" id="statScanned">{{ $opname->details->whereNotNull('scanned_at')->count() }}</div><div class="stat-label">Item Sudah Diisi</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div><div class="stat-value" id="statExpected">{{ $opname->details->sum('expected_stock') }}</div><div class="stat-label">Total Expected</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div><div class="stat-value" id="statActual">{{ $opname->details->whereNotNull('scanned_at')->sum('actual_stock') }}</div><div class="stat-label">Total Actual</div></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong>Seluruh Stock Outlet</strong>
            <span class="text-muted small ms-2">{{ $opname->details->count() }} item</span>
        </div>
        <div class="d-flex gap-2">
            <input type="search" id="searchItem" class="form-control form-control-sm" placeholder="Cari barcode / nama...">
            <button type="button" id="btnReset" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-eraser me-1"></i>Reset</button>
            <button type="button" id="btnSaveActual" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Actual</button>
            <button type="button" id="btnComplete" class="btn btn-sm btn-primary"><i class="fa-solid fa-check me-1"></i>Selesaikan</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 65vh;">
            <table class="table table-hover align-middle mb-0" id="tableOpnameItems">
                <thead class="sticky-top bg-body">
                    <tr>
                        <th>Barcode</th>
                        <th>Nama Item</th>
                        <th class="text-center">Expected</th>
                        <th class="text-center" style="width:160px;">Actual</th>
                        <th class="text-center">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opname->details as $detail)
                        <tr data-item-id="{{ $detail->item_id }}" data-search="{{ strtolower(($detail->item->barcode ?? '').' '.($detail->item->name ?? '')) }}">
                            <td>{{ $detail->item->barcode ?? '-' }}</td>
                            <td>{{ $detail->item->name ?? '(item dihapus)' }}</td>
                            <td class="text-center expected-value">{{ $detail->expected_stock }}</td>
                            <td>
                                <input type="number"
                                       min="0"
                                       class="form-control form-control-sm text-center actual-input"
                                       value="{{ $detail->scanned_at ? $detail->actual_stock : '' }}"
                                       placeholder="Wajib diisi"
                                       data-item-id="{{ $detail->item_id }}">
                            </td>
                            <td class="text-center difference-value">
                                {{ $detail->scanned_at ? $detail->actual_stock - $detail->expected_stock : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada item aktif pada outlet ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const opnameId = {{ $opname->id }};

    function collectActuals(requireAll = false) {
        const actuals = {};
        let missing = 0;

        $('.actual-input').each(function () {
            const value = $(this).val();
            if (value === '') {
                missing++;
                $(this).toggleClass('is-invalid', requireAll);
                return;
            }
            $(this).removeClass('is-invalid');
            actuals[$(this).data('item-id')] = Number(value);
        });

        return { actuals, missing };
    }

    function updateDifference(input) {
        const row = $(input).closest('tr');
        const expected = Number(row.find('.expected-value').text());
        const actual = $(input).val();
        row.find('.difference-value').text(actual === '' ? '-' : Number(actual) - expected);
    }

    function updateStats(totals) {
        $('#statScanned').text(totals.total_scanned_items);
        $('#statExpected').text(totals.total_expected);
        $('#statActual').text(totals.total_actual);
    }

    function saveActuals(requireAll = false) {
        const values = collectActuals(requireAll);
        if (requireAll && values.missing) {
            mfToast('error', values.missing + ' item belum diisi Actual Stock.');
            return $.Deferred().reject().promise();
        }
        if (!Object.keys(values.actuals).length) {
            mfToast('info', 'Belum ada Actual Stock yang diisi.');
            return $.Deferred().reject().promise();
        }

        return $.ajax({
            url: '/stock-opname/' + opnameId + '/actuals',
            method: 'POST',
            data: { actuals: values.actuals },
            success: function (res) {
                updateStats(res.totals);
            },
            error: function (xhr) {
                mfToast('error', xhr.responseJSON?.message || 'Gagal menyimpan Actual Stock.');
            }
        });
    }

    $(document).on('input', '.actual-input', function () { updateDifference(this); });

    $('#searchItem').on('input', function () {
        const term = $(this).val().toLowerCase().trim();
        $('#tableOpnameItems tbody tr[data-item-id]').each(function () {
            $(this).toggle($(this).data('search').includes(term));
        });
    });

    $('#btnSaveActual').on('click', function () {
        saveActuals(false).done(() => mfToast('success', 'Actual stock berhasil disimpan.'));
    });

    $('#btnReset').on('click', function () {
        Swal.fire({
            title: 'Reset seluruh actual stock?',
            text: 'Semua nilai Actual akan dikosongkan kembali.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reset',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.post('/stock-opname/' + opnameId + '/reset', function (res) {
                mfToast('success', res.message);
                location.reload();
            });
        });
    });

    $('#btnComplete').on('click', function () {
        const values = collectActuals(true);
        if (values.missing) {
            mfToast('error', values.missing + ' item belum diisi Actual Stock.');
            return;
        }

        Swal.fire({
            title: 'Selesaikan Stock Opname?',
            text: 'Stok Master Item akan disesuaikan berdasarkan seluruh nilai Actual.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Selesaikan',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (!result.isConfirmed) return;

            saveActuals(true).done(function () {
                $.ajax({
                    url: '/stock-opname/' + opnameId + '/complete',
                    method: 'POST',
                    success: function (res) {
                        mfToast('success', res.message);
                        setTimeout(() => window.location.href = res.redirect, 800);
                    },
                    error: function (xhr) {
                        mfToast('error', xhr.responseJSON?.message || 'Gagal menyelesaikan stock opname.');
                    }
                });
            });
        });
    });
});
</script>
@endpush

<div class="d-flex gap-1">
    @if($opname->status !== 'completed')
        <a href="{{ route('stock-opname.scan', $opname) }}" class="btn btn-sm btn-outline-primary" title="Lanjutkan Scan">
            <i class="fa-solid fa-barcode"></i>
        </a>
        @can('delete', $opname)
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-opname" data-id="{{ $opname->id }}" title="Hapus">
                <i class="fa-solid fa-trash"></i>
            </button>
        @endcan
    @else
        <a href="{{ route('stock-opname.show', $opname) }}" class="btn btn-sm btn-outline-secondary" title="Lihat Detail">
            <i class="fa-solid fa-eye"></i>
        </a>
        <a href="{{ route('stock-opname.export.pdf', $opname) }}" class="btn btn-sm btn-outline-danger" title="Export PDF">
            <i class="fa-solid fa-file-pdf"></i>
        </a>
        <a href="{{ route('stock-opname.export.excel', $opname) }}" class="btn btn-sm btn-outline-success" title="Export Excel">
            <i class="fa-solid fa-file-excel"></i>
        </a>
    @endif
</div>

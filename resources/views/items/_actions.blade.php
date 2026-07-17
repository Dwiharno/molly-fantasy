<div class="d-flex gap-1">
    <a href="{{ route('items.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit">
        <i class="fa-solid fa-pen"></i>
    </a>
    @can('delete', $item)
        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-item" data-id="{{ $item->id }}" title="Hapus">
            <i class="fa-solid fa-trash"></i>
        </button>
    @endcan
</div>

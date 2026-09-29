{{-- Form tambah barang inventaris + tombol isi daftar barang standar. --}}
@props(['classroom', 'categories', 'routePrefix'])

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route($routePrefix.'.items.store', $classroom) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Kategori</label>
                <select name="category" class="form-select" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Nama Barang</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control" maxlength="100" placeholder="mis. Proyektor" required>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Barang</button>
            </div>
        </form>
        <form action="{{ route($routePrefix.'.items.defaults', $classroom) }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-check me-1"></i>Tambah Daftar Barang Standar</button>
            <small class="text-muted ms-2">Menambahkan barang standar sekolah (mebelair, elektronik, dll.) yang belum ada di kelas ini.</small>
        </form>
    </div>
</div>

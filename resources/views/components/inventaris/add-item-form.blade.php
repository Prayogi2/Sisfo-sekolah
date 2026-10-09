{{-- Form tambah beberapa barang inventaris sekaligus + tombol isi daftar barang standar. --}}
@props(['classroom', 'categories', 'routePrefix'])

@php
    // Setelah validasi gagal, tampilkan kembali baris yang sudah diisi.
    $rows = old('items') ?: [['category' => $categories[0]->value, 'name' => '', 'quantity' => 1]];
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold"><i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i>Unggah Daftar Inventaris</div>
    <div class="card-body border-bottom">
        <p class="small text-muted">Import banyak barang sekaligus dari file Excel. Unduh template, isi kategori, nama barang, dan jumlah, lalu unggah file .xlsx.</p>
        <form action="{{ route($routePrefix.'.import', $classroom) }}" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap align-items-end gap-2">
            @csrf
            <div class="flex-grow-1">
                <label for="inventorySpreadsheet" class="form-label">File daftar inventaris (.xlsx)</label>
                <input id="inventorySpreadsheet" type="file" name="file" class="form-control" accept=".xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import Inventaris</button>
            <a href="{{ route($routePrefix.'.import.template') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Unduh Template</a>
        </form>
    </div>
    <div class="card-header bg-white fw-bold"><i class="bi bi-plus-square me-2 text-primary"></i>Tambah Barang Inventaris</div>
    <div class="card-body">
        <form action="{{ route($routePrefix.'.items.store', $classroom) }}" method="POST" id="addInventoryItemsForm">
            @csrf
            <div id="inventoryItemRows">
                @foreach($rows as $index => $row)
                    <div class="row g-2 align-items-center mb-2 inventory-item-row">
                        <div class="col-md-4">
                            <select name="items[{{ $index }}][category]" class="form-select" aria-label="Kategori barang">
                                @foreach($categories as $category)
                                    <option value="{{ $category->value }}" @selected(($row['category'] ?? null) === $category->value)>{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <input type="text" name="items[{{ $index }}][name]" value="{{ $row['name'] ?? '' }}" class="form-control @error("items.{$index}.name") is-invalid @enderror" maxlength="100" placeholder="Nama barang, mis. Proyektor" aria-label="Nama barang">
                            @error("items.{$index}.name")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-4 col-md-2">
                            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" min="0" max="9999" class="form-control @error("items.{$index}.quantity") is-invalid @enderror" placeholder="Jumlah" aria-label="Jumlah barang" required>
                            @error("items.{$index}.quantity")<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-danger remove-inventory-row" title="Hapus baris"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex flex-wrap justify-content-between gap-2 mt-3">
                <button type="button" class="btn btn-outline-primary" id="addInventoryRow"><i class="bi bi-plus-lg me-1"></i>Tambah Baris</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Semua Barang</button>
            </div>
            <small class="text-muted d-block mt-2">Jumlah yang diisi dicatat sebagai barang kondisi baik; wali kelas memperbaruinya lewat laporan kondisi. Baris dengan nama barang kosong akan diabaikan. Maksimal {{ \App\Http\Requests\Inventory\StoreInventoryItemRequest::MAX_ITEMS }} barang sekali simpan.</small>
        </form>
        <hr>
        <form action="{{ route($routePrefix.'.items.defaults', $classroom) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-check me-1"></i>Tambah Daftar Barang Standar</button>
            <small class="text-muted ms-2">Menambahkan barang standar sekolah (mebelair, elektronik, dll.) yang belum ada di kelas ini.</small>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const container = document.getElementById('inventoryItemRows');
        let nextIndex = container.querySelectorAll('.inventory-item-row').length;

        document.getElementById('addInventoryRow').addEventListener('click', function () {
            const rows = container.querySelectorAll('.inventory-item-row');
            const template = rows[rows.length - 1];
            const row = template.cloneNode(true);
            const previousCategory = template.querySelector('select').value;

            row.querySelectorAll('select, input').forEach(field => {
                field.name = field.name.replace(/items\[\d+\]/, `items[${nextIndex}]`);
                field.classList.remove('is-invalid');
            });
            row.querySelector('input[name$="[name]"]').value = '';
            row.querySelector('input[name$="[quantity]"]').value = 1;
            row.querySelectorAll('.invalid-feedback').forEach(feedback => feedback.remove());
            // Kategori baris baru mengikuti baris sebelumnya supaya cepat mengisi satu kategori.
            row.querySelector('select').value = previousCategory;
            container.appendChild(row);
            row.querySelector('input[name$="[name]"]').focus();
            nextIndex++;
        });

        container.addEventListener('click', function (event) {
            const button = event.target.closest('.remove-inventory-row');
            if (!button) return;

            const rows = container.querySelectorAll('.inventory-item-row');
            if (rows.length > 1) {
                button.closest('.inventory-item-row').remove();
            } else {
                rows[0].querySelector('input[name$="[name]"]').value = '';
            }
        });
    })();
</script>
@endpush

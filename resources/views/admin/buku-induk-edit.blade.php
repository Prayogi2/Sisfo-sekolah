@extends('layouts.app')

@section('title', $student->exists ? 'Edit Buku Induk Siswa' : 'Tambah Siswa Baru')

@php
    $profile = $student->profile;
    $academic = $student->academicRecord;
    $father = $father ?? null;
    $mother = $mother ?? null;
    $isNew = ! $student->exists;
    $backUrl = $isNew ? route('admin.data-siswa') : route('admin.buku-induk', ['student' => $student->id]);
    $value = fn (string $field, mixed $fallback = '') => old($field, $fallback);
    $guardianValue = function (string $prefix, string $field, $guardian = null) use ($value) {
        return $value($prefix.'_'.$field, $guardian?->{$field});
    };
    $guardianEnumValue = function (string $prefix, string $field, $guardian = null) use ($guardianValue) {
        $current = $guardianValue($prefix, $field, $guardian);
        return is_object($current) ? $current->value : $current;
    };
    $guardianDateValue = function (string $prefix, string $field, $guardian = null) use ($guardianValue) {
        $current = $guardianValue($prefix, $field, $guardian);
        return is_object($current) ? $current->format('Y-m-d') : $current;
    };
@endphp

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            @if($isNew)
                <div><h1 class="h3 mb-1 fw-bold">Tambah Siswa Baru</h1><p class="text-muted mb-0">Isi identitas utama. Data Buku Induk lainnya boleh dikosongkan dan dilengkapi menyusul.</p></div>
            @else
                <div><h1 class="h3 mb-1 fw-bold">Edit Buku Induk Siswa</h1><p class="text-muted mb-0">{{ $student->name }} · Lengkapi seluruh data dalam satu halaman.</p></div>
            @endif
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        </div>

        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        @if($isNew)
            <x-page-guide>Kolom bertanda <span class="text-danger">*</span> wajib diisi. Kolom lain boleh dikosongkan dan dilengkapi kapan saja lewat menu Buku Induk → Edit Buku Induk Lengkap.</x-page-guide>
        @else
            <x-page-guide>Formulir ini menyimpan semua bagian Buku Induk sekaligus. Bagian yang masih kosong boleh dilengkapi bertahap — klik Simpan Semua Data setiap kali selesai mengisi sebagian.</x-page-guide>
        @endif

        <form action="{{ $isNew ? route('admin.data-siswa.store') : route('admin.buku-induk.update', $student) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @unless($isNew)@method('PUT')@endunless
            <div class="card shadow-sm mb-4"><div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary"><i class="bi bi-person-vcard me-2"></i>Identitas Peserta Didik</h6></div><div class="card-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">NISN <span class="text-danger">*</span></label><input name="nisn" class="form-control" value="{{ $value('nisn', $student->nisn) }}" required></div>
                <div class="col-md-4"><label class="form-label">NIS <span class="text-danger">*</span></label><input name="nis" class="form-control" value="{{ $value('nis', $student->nis) }}" required></div>
                <div class="col-md-4"><label class="form-label">Nama Lengkap <span class="text-danger">*</span></label><input name="name" class="form-control" value="{{ $value('name', $student->name) }}" required></div>
                <div class="col-md-4"><label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label><select name="gender" class="form-select" required>@if($isNew)<option value="">-- Pilih --</option>@endif<option value="L" @selected($value('gender', $student->gender?->value) === 'L')>Laki-laki</option><option value="P" @selected($value('gender', $student->gender?->value) === 'P')>Perempuan</option></select></div>
                <div class="col-md-4"><label class="form-label">Tempat Lahir</label><input name="birth_place" class="form-control" value="{{ $value('birth_place', $student->birth_place) }}"></div>
                <div class="col-md-4"><label class="form-label">Tanggal Lahir</label><input name="birth_date" type="date" class="form-control" value="{{ $value('birth_date', $student->birth_date?->format('Y-m-d')) }}"></div>
                <div class="col-md-4"><label class="form-label">Kelas</label><select name="classroom_id" class="form-select"><option value="">-- Belum Ada Kelas --</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) $value('classroom_id', $student->classroom_id) === (string) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Status Siswa</label><select name="status" class="form-select">@foreach($studentStatuses as $status)<option value="{{ $status->value }}" @selected($value('status', $student->status?->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Nama Orang Tua</label><input name="parent_name" class="form-control" value="{{ $value('parent_name', $student->parent_name) }}"></div>
                <div class="col-md-4"><label class="form-label">No. Telepon Orang Tua</label><input name="parent_phone" class="form-control" value="{{ $value('parent_phone', $student->parent_phone) }}"></div>
                <div class="col-md-8"><label class="form-label">Alamat</label><textarea name="address" class="form-control" rows="2">{{ $value('address', $student->address) }}</textarea></div>
                <div class="col-md-4"><label class="form-label">Foto Siswa</label><input name="photo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp"><small class="text-muted">JPG, PNG, WEBP maksimal 5 MB.</small></div>
                @if($profile?->photo_path)<div class="col-md-2"><img src="{{ Storage::url($profile->photo_path) }}" alt="Foto {{ $student->name }}" class="img-thumbnail" style="max-height:100px"></div>@endif
            </div></div></div>

            <div class="card shadow-sm mb-4"><div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary"><i class="bi bi-person-lines-fill me-2"></i>Profil Lengkap</h6></div><div class="card-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Nama Panggilan</label><input name="nickname" class="form-control" value="{{ $value('nickname', $profile?->nickname) }}"></div>
                <div class="col-md-4"><label class="form-label">NIK</label><input name="nik" class="form-control" value="{{ $value('nik', $profile?->nik) }}"></div>
                <div class="col-md-4"><label class="form-label">No. Kartu Keluarga</label><input name="family_card_number" class="form-control" value="{{ $value('family_card_number', $profile?->family_card_number) }}"></div>
                <div class="col-md-4"><label class="form-label">Agama</label><select name="religion" class="form-select"><option value="">-- Pilih --</option>@foreach($religions as $item)<option value="{{ $item->value }}" @selected($value('religion', $profile?->religion?->value) === $item->value)>{{ $item->label() }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Status dalam Keluarga</label><select name="family_status" class="form-select"><option value="">-- Pilih --</option>@foreach($familyStatuses as $item)<option value="{{ $item->value }}" @selected($value('family_status', $profile?->family_status?->value) === $item->value)>{{ $item->label() }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Golongan Darah</label><select name="blood_type" class="form-select"><option value="">-- Pilih --</option>@foreach($bloodTypes as $item)<option value="{{ $item->value }}" @selected($value('blood_type', $profile?->blood_type?->value) === $item->value)>{{ $item->value }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Anak Ke-</label><input name="birth_order" type="number" min="1" class="form-control" value="{{ $value('birth_order', $profile?->birth_order) }}"></div>
                <div class="col-md-3"><label class="form-label">Jumlah Saudara</label><input name="siblings_count" type="number" min="0" class="form-control" value="{{ $value('siblings_count', $profile?->siblings_count) }}"></div>
                <div class="col-md-3"><label class="form-label">Berat (kg)</label><input name="weight_kg" type="number" min="0" class="form-control" value="{{ $value('weight_kg', $profile?->weight_kg) }}"></div>
                <div class="col-md-3"><label class="form-label">Tinggi (cm)</label><input name="height_cm" type="number" min="0" class="form-control" value="{{ $value('height_cm', $profile?->height_cm) }}"></div>
                <div class="col-md-6"><label class="form-label">Alamat Jalan</label><input name="street_address" class="form-control" value="{{ $value('street_address', $profile?->street_address) }}"></div>
                <div class="col-md-6"><label class="form-label">Dusun / Gang</label><input name="hamlet" class="form-control" value="{{ $value('hamlet', $profile?->hamlet) }}"></div>
                <div class="col-md-4"><label class="form-label">Desa / Kelurahan</label><input name="village" class="form-control" value="{{ $value('village', $profile?->village) }}"></div>
                <div class="col-md-4"><label class="form-label">Kecamatan</label><input name="district" class="form-control" value="{{ $value('district', $profile?->district) }}"></div>
                <div class="col-md-4"><label class="form-label">Kabupaten / Kota</label><input name="regency" class="form-control" value="{{ $value('regency', $profile?->regency) }}"></div>
                <div class="col-md-6"><label class="form-label">Provinsi</label><input name="province" class="form-control" value="{{ $value('province', $profile?->province) }}"></div>
                <div class="col-md-6"><label class="form-label">Kode Pos</label><input name="postal_code" class="form-control" value="{{ $value('postal_code', $profile?->postal_code) }}"></div>
            </div></div></div>

            @foreach([['prefix' => 'father', 'title' => 'Data Ayah', 'guardian' => $father], ['prefix' => 'mother', 'title' => 'Data Ibu', 'guardian' => $mother]] as $parent)
                @php($prefix = $parent['prefix'])
                @php($guardian = $parent['guardian'])
                <div class="card shadow-sm mb-4"><div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary"><i class="bi bi-person-heart me-2"></i>{{ $parent['title'] }}</h6></div><div class="card-body"><div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Nama</label><input name="{{ $prefix }}_name" class="form-control" value="{{ $guardianValue($prefix, 'name', $guardian) }}"></div>
                    <div class="col-md-2"><label class="form-label">Jenis Kelamin</label><select name="{{ $prefix }}_gender" class="form-select"><option value="">-- Pilih --</option><option value="L" @selected($guardianValue($prefix, 'gender', $guardian) === 'L')>Laki-laki</option><option value="P" @selected($guardianValue($prefix, 'gender', $guardian) === 'P')>Perempuan</option></select></div>
                    <div class="col-md-3"><label class="form-label">NIK</label><input name="{{ $prefix }}_nik" class="form-control" value="{{ $guardianValue($prefix, 'nik', $guardian) }}"></div>
                    <div class="col-md-3"><label class="form-label">No. Kartu Keluarga</label><input name="{{ $prefix }}_family_card_number" class="form-control" value="{{ $guardianValue($prefix, 'family_card_number', $guardian) }}"></div>
                    <div class="col-md-3"><label class="form-label">Tempat Lahir</label><input name="{{ $prefix }}_birth_place" class="form-control" value="{{ $guardianValue($prefix, 'birth_place', $guardian) }}"></div>
                    <div class="col-md-3"><label class="form-label">Tanggal Lahir</label><input name="{{ $prefix }}_birth_date" type="date" class="form-control" value="{{ $guardianDateValue($prefix, 'birth_date', $guardian) }}"></div>
                    <div class="col-md-3"><label class="form-label">Agama</label><select name="{{ $prefix }}_religion" class="form-select"><option value="">-- Pilih --</option>@foreach($religions as $item)<option value="{{ $item->value }}" @selected($guardianEnumValue($prefix, 'religion', $guardian) === $item->value)>{{ $item->label() }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Golongan Darah</label><select name="{{ $prefix }}_blood_type" class="form-select"><option value="">-- Pilih --</option>@foreach($bloodTypes as $item)<option value="{{ $item->value }}" @selected($guardianEnumValue($prefix, 'blood_type', $guardian) === $item->value)>{{ $item->value }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Pendidikan Terakhir</label><select name="{{ $prefix }}_last_education" class="form-select"><option value="">-- Pilih --</option>@foreach($educationLevels as $item)<option value="{{ $item->value }}" @selected($guardianEnumValue($prefix, 'last_education', $guardian) === $item->value)>{{ $item->label() }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Pekerjaan</label><input name="{{ $prefix }}_occupation" class="form-control" value="{{ $guardianValue($prefix, 'occupation', $guardian) }}"></div>
                    <div class="col-md-3"><label class="form-label">Penghasilan per Bulan</label><input name="{{ $prefix }}_monthly_income" class="form-control" value="{{ $guardianValue($prefix, 'monthly_income', $guardian) }}"></div>
                    <div class="col-md-3"><label class="form-label">No. Kontak WhatsApp</label><input name="{{ $prefix }}_phone" class="form-control" value="{{ $guardianValue($prefix, 'phone', $guardian) }}"></div>
                    <div class="col-12"><label class="form-label">Alamat</label><textarea name="{{ $prefix }}_address" class="form-control" rows="2">{{ $guardianValue($prefix, 'address', $guardian) }}</textarea></div>
                </div></div></div>
            @endforeach

            <div class="card shadow-sm mb-4"><div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary"><i class="bi bi-clock-history me-2"></i>Riwayat Pendidikan & Kelulusan</h6></div><div class="card-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Asal TK / PAUD</label><input name="kindergarten_origin" class="form-control" value="{{ $value('kindergarten_origin', $academic?->kindergarten_origin) }}"></div>
                <div class="col-md-4"><label class="form-label">No. Ijazah TK</label><input name="kindergarten_certificate_number" class="form-control" value="{{ $value('kindergarten_certificate_number', $academic?->kindergarten_certificate_number) }}"></div>
                <div class="col-md-4"><label class="form-label">Tanggal Ijazah TK</label><input name="kindergarten_certificate_date" type="date" class="form-control" value="{{ $value('kindergarten_certificate_date', $academic?->kindergarten_certificate_date?->format('Y-m-d')) }}"></div>
                <div class="col-md-4"><label class="form-label">Status Masuk</label><input name="entry_status" class="form-control" value="{{ $value('entry_status', $academic?->entry_status) }}"></div>
                <div class="col-md-4"><label class="form-label">Tanggal Masuk</label><input name="entry_date" type="date" class="form-control" value="{{ $value('entry_date', $academic?->entry_date?->format('Y-m-d')) }}"></div>
                <div class="col-md-4"><label class="form-label">Status Kelulusan</label><select name="graduation_status" class="form-select"><option value="">-- Pilih --</option>@foreach($graduationStatuses as $item)<option value="{{ $item->value }}" @selected($value('graduation_status', $academic?->graduation_status?->value) === $item->value)>{{ $item->label() }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Tanggal Pindah Keluar</label><input name="transfer_out_date" type="date" class="form-control" value="{{ $value('transfer_out_date', $academic?->transfer_out_date?->format('Y-m-d')) }}"></div>
                <div class="col-md-8"><label class="form-label">Alasan Pindah Keluar</label><input name="transfer_out_reason" class="form-control" value="{{ $value('transfer_out_reason', $academic?->transfer_out_reason) }}"></div>
                <div class="col-md-4"><label class="form-label">Tanggal Keluar</label><input name="exit_date" type="date" class="form-control" value="{{ $value('exit_date', $academic?->exit_date?->format('Y-m-d')) }}"></div>
                <div class="col-md-8"><label class="form-label">Alasan Keluar</label><input name="exit_reason" class="form-control" value="{{ $value('exit_reason', $academic?->exit_reason) }}"></div>
                <div class="col-md-4"><label class="form-label">Tahun Kelulusan</label><input name="graduation_year" type="number" min="1900" max="2200" class="form-control" value="{{ $value('graduation_year', $academic?->graduation_year) }}"></div>
                <div class="col-md-4"><label class="form-label">No. Ijazah</label><input name="graduation_certificate_number" class="form-control" value="{{ $value('graduation_certificate_number', $academic?->graduation_certificate_number) }}"></div>
                <div class="col-md-4"><label class="form-label">Tanggal Ijazah</label><input name="graduation_certificate_date" type="date" class="form-control" value="{{ $value('graduation_certificate_date', $academic?->graduation_certificate_date?->format('Y-m-d')) }}"></div>
                <div class="col-md-6"><label class="form-label">Melanjutkan Ke</label><input name="continued_to" class="form-control" value="{{ $value('continued_to', $academic?->continued_to) }}"></div>
                <div class="col-md-6"><label class="form-label">Catatan Kelulusan</label><textarea name="graduation_notes" class="form-control" rows="2">{{ $value('graduation_notes', $academic?->graduation_notes) }}</textarea></div>
            </div></div></div>

            @unless($isNew)
            <div class="card shadow-sm mb-4"><div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary"><i class="bi bi-graph-up-arrow me-2"></i>Perkembangan Akademik</h6></div><div class="card-body"><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Tahun Ajaran</label><input name="progress_academic_year" class="form-control" value="{{ $value('progress_academic_year', $currentProgressNote?->academic_year ?? \App\Models\Classroom::currentAcademicYear()) }}" required></div>
                <div class="col-md-4"><label class="form-label">Semester</label><select name="progress_semester" class="form-select">@foreach($semesters as $item)<option value="{{ $item->value }}" @selected($value('progress_semester', $currentProgressNote?->semester?->value ?? \App\Enums\Semester::current()->value) === $item->value)>{{ $item->label() }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Kenaikan Kelas</label><select name="promotion_status" class="form-select">@foreach($promotionStatuses as $item)<option value="{{ $item->value }}" @selected($value('promotion_status', $currentProgressNote?->promotion_status?->value ?? \App\Enums\PromotionStatus::Undecided->value) === $item->value)>{{ $item->label() }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Catatan Perkembangan Siswa</label><textarea name="progress_notes" class="form-control" rows="4" placeholder="Catatan wali kelas/guru tentang perkembangan siswa">{{ $value('progress_notes', $currentProgressNote?->notes) }}</textarea></div>
            </div></div></div>
            @endunless

            <div class="d-flex justify-content-end gap-2 mb-5"><a href="{{ $backUrl }}" class="btn btn-secondary">Batal</a><button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>{{ $isNew ? 'Simpan Siswa Baru' : 'Simpan Semua Data' }}</button></div>
        </form>
    </div>
@endsection

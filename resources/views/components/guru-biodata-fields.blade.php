{{--
    Field biodata guru untuk modal Tambah & Edit Guru. Di modal edit, isi
    `idPrefix` supaya script bisa mengisi nilainya dari tombol Edit.
--}}
@props(['idPrefix' => null])

@php
    // Atribut id hanya dicetak di modal edit (yang punya prefix).
    $id = fn (string $field) => $idPrefix ? 'id="'.e($idPrefix.\Illuminate\Support\Str::studly($field)).'"' : '';
@endphp

<div class="col-md-6"><label class="form-label fw-semibold">NIK</label><input type="text" name="nik" {!! $id('nik') !!} class="form-control" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" title="NIK 16 digit angka"></div>
<div class="col-md-3"><label class="form-label fw-semibold">Tempat Lahir</label><input type="text" name="birth_place" {!! $id('birth_place') !!} class="form-control"></div>
<div class="col-md-3"><label class="form-label fw-semibold">Tanggal Lahir</label><input type="date" name="birth_date" {!! $id('birth_date') !!} class="form-control" max="{{ now()->subDay()->format('Y-m-d') }}"></div>
<div class="col-md-6">
    <label class="form-label fw-semibold">Pendidikan Terakhir</label>
    <select name="last_education" {!! $id('last_education') !!} class="form-select">
        <option value="">-- Pilih --</option>
        @foreach (\App\Enums\EducationLevel::cases() as $level)
            <option value="{{ $level->value }}">{{ $level->label() }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6">
    <label class="form-label fw-semibold">Golongan Darah</label>
    <select name="blood_type" {!! $id('blood_type') !!} class="form-select">
        <option value="">-- Pilih --</option>
        @foreach (\App\Enums\BloodType::cases() as $bloodType)
            <option value="{{ $bloodType->value }}">{{ $bloodType->value }}</option>
        @endforeach
    </select>
</div>
<div class="col-12"><label class="form-label fw-semibold">Alamat (Jalan / Dusun / RT-RW)</label><input type="text" name="address" {!! $id('address') !!} class="form-control" maxlength="500"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Desa / Kelurahan</label><input type="text" name="village" {!! $id('village') !!} class="form-control"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Kecamatan</label><input type="text" name="district" {!! $id('district') !!} class="form-control"></div>
<div class="col-md-4"><label class="form-label fw-semibold">Provinsi</label><input type="text" name="province" {!! $id('province') !!} class="form-control"></div>

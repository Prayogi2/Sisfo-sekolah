<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Mendaftarkan satu siswa baru: data identitas + akun login siswa +
 * (opsional) data Buku Induk lainnya. Dipakai oleh form Tambah Siswa dan
 * oleh impor dari Excel, supaya keduanya membuat siswa dengan cara yang
 * sama persis.
 */
class StudentEnroller
{
    /**
     * Field identitas inti Student — dipakai di sini dan oleh StudentImporter
     * saat memperbarui siswa yang sudah ada, supaya kedua tempat tidak
     * menyimpang satu sama lain.
     */
    public const IDENTITY_FIELDS = [
        'nisn', 'nis', 'name', 'gender', 'classroom_id', 'birth_place', 'birth_date',
        'address', 'parent_name', 'parent_phone', 'status',
    ];

    public function __construct(private StudentRecordWriter $recordWriter) {}

    /**
     * @param  array<string, mixed>  $data  Identitas siswa (nisn, nis, name, gender, ...) plus field opsional Buku Induk yang dipahami StudentRecordWriter.
     */
    public function enroll(array $data, ?UploadedFile $photo = null): Student
    {
        return DB::transaction(function () use ($data, $photo) {
            $student = Student::create(collect($data)->only(self::IDENTITY_FIELDS)->filter(fn ($value) => $value !== null)->all());

            $loginIdentifier = $student->nis ?: $student->nisn;

            $student->update(['user_id' => User::create([
                'name' => $student->name,
                'email' => $loginIdentifier.'@siswa.local',
                'username' => $loginIdentifier,
                'password' => 'password123',
                'role' => 'siswa',
            ])->id]);

            $this->recordWriter->save($student, $data, $photo);

            return $student;
        });
    }
}

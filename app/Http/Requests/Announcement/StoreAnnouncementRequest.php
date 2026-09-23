<?php

namespace App\Http\Requests\Announcement;

use App\Enums\AnnouncementCategory;
use App\Enums\AnnouncementTarget;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * Kosongkan published_at untuk kirim langsung; isi untuk menjadwalkan.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::enum(AnnouncementCategory::class)],
            'target' => ['required', Rule::enum(AnnouncementTarget::class)],
            'classroom_id' => [Rule::requiredIf($this->input('target') === AnnouncementTarget::Classroom->value), 'nullable', 'integer', 'exists:classrooms,id'],
            'student_ids' => [Rule::requiredIf($this->input('target') === AnnouncementTarget::SpecificStudents->value), 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul notifikasi wajib diisi.',
            'title.max' => 'Judul notifikasi maksimal 150 karakter.',
            'message.required' => 'Isi pesan wajib diisi.',
            'classroom_id.required' => 'Pilih kelas penerima.',
            'student_ids.required' => 'Pilih minimal satu siswa penerima.',
            'published_at.date' => 'Tanggal kirim tidak valid.',
        ];
    }
}

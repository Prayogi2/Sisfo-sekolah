<?php

namespace App\Http\Requests\StudentRecitation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRecitationNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manageRecitationNotes', $this->route('classroom')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where('classroom_id', $this->route('classroom')->id),
            ],
            'type' => ['required', 'in:memorization,reading'],
            'material' => ['required', 'string', 'max:255'],
            'achievement' => ['required', 'string', 'max:255'],
            'recorded_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Siswa wajib dipilih.',
            'student_id.exists' => 'Siswa yang dipilih bukan bagian dari kelas ini.',
            'type.required' => 'Pilih jenis catatan.',
            'material.required' => 'Surah, materi, atau halaman wajib diisi.',
            'achievement.required' => 'Capaian siswa wajib diisi.',
            'recorded_at.required' => 'Tanggal pencatatan wajib diisi.',
        ];
    }
}

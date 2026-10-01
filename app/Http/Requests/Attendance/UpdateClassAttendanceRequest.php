<?php

namespace App\Http\Requests\Attendance;

use App\Enums\AttendanceStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAttendance', $this->route('classroom'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['nullable', Rule::enum(AttendanceStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'Absensi tidak bisa diisi untuk tanggal yang belum terjadi.',
            'statuses.required' => 'Belum ada siswa di kelas ini.',
            'statuses.*.enum' => 'Status absensi tidak valid.',
        ];
    }
}

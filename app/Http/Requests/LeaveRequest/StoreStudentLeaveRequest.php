<?php

namespace App\Http\Requests\LeaveRequest;

use App\Enums\LeaveType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentLeaveRequest extends FormRequest
{
    /**
     * Izin boleh diajukan menyusul paling lambat 7 hari, dan paling lama 14 hari.
     */
    public const MAX_DAYS_BACK = 7;

    public const MAX_DAYS = 14;

    public function authorize(): bool
    {
        return $this->user()->hasRole('siswa');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LeaveType::class)],
            'start_date' => ['required', 'date', 'after_or_equal:'.now()->subDays(self::MAX_DAYS_BACK)->toDateString()],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', function (string $attribute, mixed $value, \Closure $fail) {
                $start = strtotime((string) $this->input('start_date'));
                if ($start && strtotime((string) $value) && (strtotime((string) $value) - $start) / 86400 + 1 > self::MAX_DAYS) {
                    $fail('Izin maksimal '.self::MAX_DAYS.' hari sekali pengajuan.');
                }
            }],
            'reason' => ['required', 'string', 'max:1000'],
            'guardian_id' => ['nullable', 'integer'],
            'applicant_name' => ['required_without:guardian_id', 'nullable', 'string', 'max:100'],
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Pilih jenis izin (sakit atau izin).',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.after_or_equal' => 'Izin hanya bisa diajukan menyusul paling lambat '.self::MAX_DAYS_BACK.' hari.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'reason.required' => 'Tuliskan alasan / keterangan izin.',
            'applicant_name.required_without' => 'Pilih orang tua/wali yang mengajukan, atau tulis nama pengaju.',
            'attachment.required' => 'Lampirkan bukti (foto surat dokter / surat orang tua).',
            'attachment.mimes' => 'Bukti harus berupa foto (JPG/PNG/WEBP) atau PDF.',
            'attachment.max' => 'Ukuran bukti maksimal 5 MB.',
        ];
    }
}

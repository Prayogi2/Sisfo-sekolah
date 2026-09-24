<?php

namespace App\Http\Requests\ReportBook;

use App\Services\ReportBook;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReportBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student'));
    }

    /**
     * Aturan yang sama dipakai import Excel (lihat ReportBook::rules()).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ReportBook::rules();
    }

    public function messages(): array
    {
        return ReportBook::messages();
    }
}

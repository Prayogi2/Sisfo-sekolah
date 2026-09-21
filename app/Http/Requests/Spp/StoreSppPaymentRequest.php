<?php

namespace App\Http\Requests\Spp;

use App\Models\SppPayment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSppPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SppPayment::class);
    }

    /**
     * Kepemilikan tagihan (tagihan ini memang milik anak yang sedang dipilih)
     * dicek di controller, bukan di sini, supaya tidak bisa ditembus dengan
     * mengirim spp_bill_id milik siswa lain.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'spp_bill_id' => ['required', 'integer', 'exists:spp_bills,id'],
            'amount' => ['required', 'integer', 'min:1000'],
            'proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'spp_bill_id.required' => 'Tagihan yang dibayar wajib dipilih.',
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.min' => 'Nominal pembayaran minimal Rp1.000.',
            'proof.required' => 'Bukti pembayaran wajib diunggah.',
            'proof.mimes' => 'Bukti pembayaran harus berupa file PDF, JPG, atau PNG.',
            'proof.max' => 'Ukuran bukti pembayaran maksimal 2MB.',
        ];
    }
}

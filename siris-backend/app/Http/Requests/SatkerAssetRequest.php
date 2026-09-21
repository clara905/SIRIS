<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SatkerAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('satker') && $this->user()->is_active;
    }

    public function rules(): array
    {
        return [
            'nama_aset' => ['required', 'string', 'max:255'],
            'kategori' => ['required', Rule::in(['physical', 'software', 'digital'])],
            'kondisi' => ['nullable', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:10000'],
            'idx' => ['nullable', 'integer', 'min:1', 'max:2147483647', Rule::unique('assets', 'idx')->ignore($this->route('id'))],
            'jumlah' => ['sometimes', 'required', 'integer', 'min:1', 'max:2147483647'],
            'merk' => ['nullable', 'string', 'max:255'],
            'snumber' => ['nullable', 'string', 'max:255'],
            'pengadaan' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'nilai_kekritisan' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}

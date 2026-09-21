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
        return ['nama_aset' => ['required', 'string', 'max:255'], 'kategori' => ['required', Rule::in(['physical', 'software', 'digital'])], 'kondisi' => ['required', 'string', 'max:100'], 'deskripsi' => ['nullable', 'string', 'max:10000']];
    }
}

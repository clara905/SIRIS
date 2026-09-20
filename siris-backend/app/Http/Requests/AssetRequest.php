<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['noinven' => 'kode_aset', 'nama' => 'nama_aset', 'stock' => 'jumlah', 'keterangan' => 'deskripsi'] as $field => $legacy) {
            if (! $this->exists($field) && $this->exists($legacy)) {
                $this->merge([$field => $this->input($legacy)]);
            }
        }
        if (! $this->route('asset') && ! $this->exists('kategori')) {
            $this->merge(['kategori' => 'physical']);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idx' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2147483647', Rule::unique('assets', 'idx')->ignore($this->route('asset'))],
            'noinven' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('assets', 'kode_aset')->ignore($this->route('asset'))],
            'nama' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'kondisi' => ['sometimes', 'nullable', 'string', 'max:100'],
            'merk' => ['sometimes', 'nullable', 'string', 'max:255'],
            'snumber' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pengadaan' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'lokasi' => ['sometimes', 'nullable', 'string', 'max:255'],
            'keterangan' => ['sometimes', 'nullable', 'string'],
            'kategori' => ['sometimes', 'required', Rule::in(['physical', 'software', 'digital'])],
            'bidang_id' => ['sometimes', 'nullable', 'exists:bidangs,id'],
            'sub_bidang_id' => ['sometimes', 'nullable', 'exists:sub_bidangs,id'],
            'satker_id' => ['sometimes', 'nullable', 'exists:satkers,id'],
            'nilai_kekritisan' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    /** @return array<string, mixed> */
    public function assetAttributes(): array
    {
        $attributes = $this->validated();
        foreach (['noinven' => 'kode_aset', 'nama' => 'nama_aset', 'stock' => 'jumlah', 'keterangan' => 'deskripsi'] as $field => $legacy) {
            if (array_key_exists($field, $attributes)) {
                $attributes[$legacy] = $attributes[$field];
                unset($attributes[$field]);
            }
        }

        if (! $this->route('asset') && ! isset($attributes['kode_aset'])) {
            $attributes['kode_aset'] = 'AST-'.Str::uuid();
        }

        return $attributes;
    }
}

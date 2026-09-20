<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RiskAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'threat_id' => ['required_without:threat_ids', 'integer', Rule::exists('threats', 'id')->where(function ($query) {
                $query->where('is_active', true)->where(function ($query) {
                    $query->whereNull('asset_id')->orWhere('asset_id', $this->integer('asset_id'));
                });
            })],
            'threat_ids' => ['sometimes', 'required', 'array', 'min:1', 'max:100'],
            'threat_ids.*' => ['required', 'distinct', 'integer', Rule::exists('threats', 'id')->where(function ($query) {
                $query->where('is_active', true)->where(function ($query) {
                    $query->whereNull('asset_id')->orWhere('asset_id', $this->integer('asset_id'));
                });
            })],
            'vulnerability_ids' => ['sometimes', 'array', 'max:100'],
            'vulnerability_ids.*' => ['integer', 'distinct', 'exists:vulnerabilities,id'],
            'likelihood' => ['required', 'integer', 'between:1,5'],
            'impact' => ['required', 'integer', 'between:1,5'],
            'tanggal_penilaian' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return ['threat_id.exists' => 'Pilih ancaman aktif yang terkait dengan aset ini atau ancaman umum.'];
    }
}

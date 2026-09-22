<?php

namespace App\Http\Requests;

use App\Models\Vulnerability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RiskSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('satker') && $this->user()->is_active;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'threat_ids' => [$this->input('action') === 'submit' ? 'required' : 'present', 'array', 'max:100'],
            'threat_ids.*' => ['integer', 'distinct', Rule::exists('threats', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'vulnerability_ids' => ['present', 'array', 'max:100'],
            'vulnerability_ids.*' => ['integer', 'distinct', Rule::exists('vulnerabilities', 'id')->where('is_active', true)],
            'new_vulnerabilities' => ['present', 'array', 'max:30'],
            'new_vulnerabilities.*.nama' => ['required', 'string', 'max:255'],
            'new_vulnerabilities.*.deskripsi' => ['nullable', 'string', 'max:5000'],
            'new_vulnerabilities.*.threat_ids' => ['required', 'array', 'min:1', 'max:100'],
            'new_vulnerabilities.*.threat_ids.*' => ['integer', 'distinct', Rule::in(is_array($this->input('threat_ids')) ? $this->input('threat_ids') : [])],
            'catatan' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->input('action') === 'submit' && count($this->input('vulnerability_ids')) + count($this->input('new_vulnerabilities')) === 0) {
                $validator->errors()->add('vulnerability_ids', 'Pilih atau identifikasi minimal satu kerentanan.');
            }
            $valid = Vulnerability::whereIn('id', $this->input('vulnerability_ids'))->where('is_active', true)->count();
            if ($valid !== count($this->input('vulnerability_ids'))) {
                $validator->errors()->add('vulnerability_ids', 'Kerentanan yang dipilih tidak tersedia.');
            }
        }];
    }
}

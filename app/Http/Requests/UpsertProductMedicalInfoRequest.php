<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertProductMedicalInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'indications'       => ['nullable', 'string'],
            'contraindications' => ['nullable', 'string'],
            'overdose'          => ['nullable', 'string'],
            'pregnancy_safety'  => ['nullable', 'string'],
            'lactation_safety'  => ['nullable', 'string'],
            'warnings'          => ['nullable', 'string'],
            'side_effects'      => ['nullable', 'string'],
            'drug_interactions' => ['nullable', 'string'],
            'dose_info'         => ['nullable', 'string'],
        ];
    }
}

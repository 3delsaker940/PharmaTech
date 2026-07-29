<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckDrugInteractionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_ids'   => ['required', 'array', 'min:2'],
            'product_ids.*' => ['required', 'integer', 'exists:products,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_ids.required' => 'Product IDs are required.',
            'product_ids.array'    => 'Product IDs must be provided as an array.',
            'product_ids.min'      => 'At least two products are required to check for drug interactions.',
            'product_ids.*.exists' => 'One or more selected products do not exist in the system.',
        ];
    }
}

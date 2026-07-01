<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $pharmacyId = $this->attributes->get('pharmacy')->id;
        $productId = $this->route('product')->id;

        return [
            'barcode' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')
                    ->where('pharmacy_id', $pharmacyId)
                    ->whereNull('deleted_at')
                    ->ignore($productId),
            ],
            'brand_name' => ['sometimes', 'string', 'max:255'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'ar_name' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:100'],
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'base_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'selling_unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'prescription_required' => ['sometimes', 'boolean'],
            'buying_price' => ['sometimes', 'numeric', 'min:0'],
            'selling_price'=> ['sometimes',  'numeric', 'min:0'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'discount_rate'=> ['sometimes', 'numeric', 'min:0', 'max:100'],
            'min_stock' => ['sometimes', 'integer', 'min:0'],
            'units_per_base' => ['sometimes', 'integer', 'min:1'],
            'allow_partial_selling' => ['sometimes', 'boolean'],
            'shelf' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'barcode.unique' => 'This barcode is already used by another product in this pharmacy.',
            'category_id.exists' => 'The selected category does not exist or has been deleted.',
            'company_id.exists' => 'The selected company does not exist or has been deleted.',
            'base_unit_id.exists' => 'The selected unit does not exist or has been deleted.',
            'selling_unit_id.exists' => 'The selected unit does not exist or has been deleted.',
            'max_stock.gt' => 'Maximum stock must be greater than minimum stock.',
        ];
    }
}

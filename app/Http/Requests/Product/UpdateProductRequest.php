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
        $product = $this->route('product');

        return [
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('pharmacy_id', $pharmacyId)
                    ->whereNull('deleted_at'),
            ],
            'barcode' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')
                    ->where('pharmacy_id', $pharmacyId)
                    ->ignore($product),
            ],
            'brand_name' => ['sometimes', 'required', 'string', 'max:255'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'prescription_required' => ['boolean'],
            'buying_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'selling_price'=> ['sometimes', 'required', 'numeric', 'min:0'],
            'tax_rate' => ['numeric', 'min:0', 'max:100'],
            'discount_rate'=> ['numeric', 'min:0', 'max:100'],
            'min_stock' => ['integer', 'min:0'],
            'base_unit'  => ['nullable', 'string', 'max:50'],
            'selling_unit' => ['nullable', 'string', 'max:50'],
            'units_per_base' => ['integer', 'min:1'],
            'allow_partial_selling' => ['boolean'],
            'image_path' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'barcode.unique' => 'This barcode is already used by another product in this pharmacy.',
            'category_id.exists' => 'The selected category does not exist or has been deleted.',
        ];
    }
}

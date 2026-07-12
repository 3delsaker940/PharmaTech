<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerReturnInvoiceRequest extends FormRequest
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

        return [
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('pharmacy_id', $pharmacyId),
            ],
            'original_sales_invoice_id' => [
                'nullable',
                'integer',
                Rule::exists('sales_invoices', 'id')->where('pharmacy_id', $pharmacyId),
            ],
            'invoice_date' => ['required', 'date'],
            'refund_method'=> ['required', Rule::in(['cash', 'credit'])],
            'reason'=> ['nullable', 'string', 'max:1000'],
            'notes'=> ['nullable', 'string', 'max:2000'],

            'items'=> ['required', 'array', 'min:1'],
            'items.*.product_id'=> [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('pharmacy_id', $pharmacyId),
            ],
            'items.*.quantity'=> ['required', 'integer', 'min:1'],
            'items.*.unit_price'=> ['required', 'numeric', 'min:0'],
            'items.*.tax'=> ['sometimes', 'numeric', 'min:0'],
            'items.*.discount'=> ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'=> 'A return invoice must have at least one item.',
            'items.*.product_id.required'=> 'Each item must have a product.',
            'items.*.product_id.exists'=> 'One or more products do not exist in this pharmacy.',
            'items.*.quantity.min'=> 'Quantity must be at least 1.',
            'items.*.unit_price.min'=> 'Unit price cannot be negative.',
        ];
    }
}

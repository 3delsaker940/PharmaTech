<?php

namespace App\Http\Requests;

use App\Models\CashBox;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSalesInvoiceRequest extends FormRequest
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
            'invoice_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'credit', 'debt'])],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('pharmacy_id', $pharmacyId)->where('deleted_at', null),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.selling_price'=> ['required', 'numeric', 'min:0'],
            'items.*.tax' => ['sometimes', 'numeric', 'min:0'],
            'items.*.discount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
    public function messages(): array
    {
        return [
            'items.required' => 'A sales invoice must have at least one item.',
            'items.*.product_id.required' => 'Each item must have a product.',
            'items.*.product_id.exists' => 'One or more products do not exist in this pharmacy.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.selling_price.min' => 'Selling price cannot be negative.',
            'customer_id.exists' => 'The selected customer does not exist in this pharmacy.',
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            $grandTotal = 0;
            foreach ($items as $item) {
                $lineSubtotal = (float) ($item['quantity'] ?? 0) * (float) ($item['selling_price'] ?? 0);
                $grandTotal  += $lineSubtotal
                    + (float) ($item['tax'] ?? 0)
                    - (float) ($item['discount'] ?? 0);
            }
            $amountPaid = (float) $this->input('amount_paid', 0);
            if ($amountPaid > round($grandTotal, 2)) {
                $validator->errors()->add(
                    'amount_paid',
                    'Amount paid (' . number_format($amountPaid, 2) . ') cannot exceed the invoice grand total (' . number_format($grandTotal, 2) . ').'
                );
            }

            if ($this->input('payment_method') === 'cash' && $amountPaid > 0) {
                $pharmacyId = $this->attributes->get('pharmacy')->id;
                $hasCashBox = CashBox::where('pharmacy_id', $pharmacyId)->exists();

                if (! $hasCashBox) {
                    $validator->errors()->add(
                        'payment_method',
                        'No cash box found. Please set up your cash box before recording a cash payment.'
                    );
                }
            }
        });
    }
}

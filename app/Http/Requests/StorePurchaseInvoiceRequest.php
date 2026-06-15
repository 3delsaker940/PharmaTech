<?php

namespace App\Http\Requests;

use App\Models\CashBox;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class StorePurchaseInvoiceRequest extends FormRequest
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
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')
                    ->where('pharmacy_id', $pharmacyId)
                    ->where('deleted_at', null),
            ],
            'invoice_date'   => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,credit,debt'],
            'amount_paid'    => ['required', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:2000'],

            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'     => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('pharmacy_id', $pharmacyId)
                    ->where('deleted_at', null),
            ],
            'items.*.quantity'        => ['required', 'integer', 'min:1'],
            'items.*.wholesale_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax'             => ['nullable', 'numeric', 'min:0'],
            'items.*.discount'        => ['nullable', 'numeric', 'min:0'],

            'items.*.batch_number'  => ['nullable', 'string', 'max:255'],
            'items.*.expiry_date'   => ['nullable', 'date'],
            'items.*.selling_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.exists'         => 'The selected supplier does not exist or is inactive.',
            'items.required'             => 'At least one item is required.',
            'items.*.product_id.exists'  => 'One of the selected products does not exist or has been removed.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            $grandTotal = 0;

            foreach ($items as $item) {
                $lineSubtotal = (float) ($item['quantity'] ?? 0) * (float) ($item['wholesale_price'] ?? 0);
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

                $hasActiveCashBox = CashBox::where('pharmacy_id', $pharmacyId)
                    ->where('status', 'active')
                    ->exists();

                if (! $hasActiveCashBox) {
                    $validator->errors()->add(
                        'payment_method',
                        'No active cash box found. Open a cash box before recording a cash payment.'
                    );
                }
            }

        });
    }
}


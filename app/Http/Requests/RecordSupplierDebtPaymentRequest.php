<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RecordSupplierDebtPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $debt = $this->route('supplierDebt');

            if (! $debt) {
                return;
            }

            if (in_array($debt->status, ['paid', 'cancelled'])) {
                $validator->errors()->add(
                    'amount',
                    "This debt is already {$debt->status} and cannot receive further payments."
                );
                return;
            }
            $amount = (float) $this->input('amount', 0);

            if ($amount > $debt->remaining_amount) {
                $validator->errors()->add(
                    'amount',
                    'Payment amount (' . number_format($amount, 2) . ') cannot exceed the remaining debt (' . number_format($debt->remaining_amount, 2) . ').'
                );
            }
        });
    }
}

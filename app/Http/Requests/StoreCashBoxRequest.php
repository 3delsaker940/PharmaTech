<?php

namespace App\Http\Requests;

use App\Models\CashBox;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCashBoxRequest extends FormRequest
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
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
    public function messages(): array
    {
        return [
            'opening_balance.required' => 'Please enter the current amount of cash in your cash box. Enter 0 if empty.',
            'opening_balance.min' => 'Opening balance cannot be negative.',
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $pharmacyId = $this->attributes->get('pharmacy')->id;

            $exists = CashBox::where('pharmacy_id', $pharmacyId)->exists();

            if ($exists) {
                $validator->errors()->add(
                    'opening_balance',
                    'This pharmacy already has a cash box.'
                );
            }
        });
    }
}

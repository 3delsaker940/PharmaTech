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
            'name'            => ['required', 'string', 'max:255'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $pharmacyId = $this->attributes->get('pharmacy')->id;

            $hasActive = CashBox::where('pharmacy_id', $pharmacyId)
                ->where('status', 'active')
                ->exists();

            if ($hasActive) {
                $validator->errors()->add(
                    'name',
                    'An active cash box already exists for this pharmacy. Close it before opening a new one.'
                );
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StorePharmacistRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'father_name' => ['nullable', 'sometimes', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $hash = User::hashForLookup(User::normalizeEmail($value));
                    if (User::where('email_hash', $hash)->exists()) {
                        $fail('The email has already been taken.');
                    }
                },
            ],
            'phone_number' => [
                'required',
                'string',
                'regex:/^(?:\+9639|09|009639)\d{8}$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $hash = User::hashForLookup(User::normalizePhone($value));
                    if (User::where('phone_hash', $hash)->exists()) {
                        $fail('The phone number has already been taken.');
                    }
                },
            ],
        ];
    }
}

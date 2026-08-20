<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            'password' =>
            [
                'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->numbers(),
                'confirmed'
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'father_name' => ['nullable','sometimes', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
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
            'pharmacy_name' => ['required', 'string', 'max:255'],
            // 'governorate_id' => ['required', 'exists:governorates,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:255', 'sometimes'],
        ];
    }
}

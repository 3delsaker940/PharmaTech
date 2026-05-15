<?php

namespace App\Http\Requests;

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
            'email' => ['required', 'email', 'unique:users,email'],
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
                'unique:users,phone_number',
                'string',
                'regex:/^(?:\+9639|09|009639)\d{8}$/'
            ],
            'pharmacy_name' => ['required', 'string', 'max:255'],
            // 'governorate_id' => ['required', 'exists:governorates,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:255', 'sometimes'],
            'licence_number' => ['required', 'string', 'max:255']
        ];
    }
}

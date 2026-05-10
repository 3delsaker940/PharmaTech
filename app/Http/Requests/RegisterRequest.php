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
            ]
        ];
    }
}

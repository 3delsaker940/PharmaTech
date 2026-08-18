<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePharmacistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pharmacistId = $this->route('id');

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'father_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($pharmacistId),
            ],
            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'regex:/^(?:\+9639|09|009639)\d{8}$/',
                Rule::unique('users', 'phone_number')->ignore($pharmacistId),
            ],
        ];
    }
}

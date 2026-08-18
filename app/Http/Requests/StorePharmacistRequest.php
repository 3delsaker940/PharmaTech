<?php

namespace App\Http\Requests;

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
            'email' => ['required', 'email', 'unique:users,email'],
            'phone_number' => [
                'required',
                'unique:users,phone_number',
                'string',
                'regex:/^(?:\+9639|09|009639)\d{8}$/'
            ],
        ];
    }
}

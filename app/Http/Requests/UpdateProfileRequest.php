<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'father_name' => ['nullable', 'sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone_number' => [
                'sometimes',
                'string',
                Rule::unique('users', 'phone_number')->ignore($this->user()?->id),
                'regex:/^(?:\+9639|09|009639)\d{8}$/'
            ]
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allowedFields = ['first_name', 'father_name', 'last_name', 'phone_number'];

            if (! $this->hasAny($allowedFields)) {
                $validator->errors()->add(
                    'payload',
                    'At least one valid field must be provided to update the profile.'
                );
            }
        });
    }
}

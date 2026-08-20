<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

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
                function (string $attribute, mixed $value, \Closure $fail) use ($pharmacistId) {
                    $hash = User::hashForLookup(User::normalizeEmail($value));
                    $existing = User::where('email_hash', $hash)
                        ->where('id', '!=', $pharmacistId)
                        ->exists();
                    if ($existing) {
                        $fail('The email has already been taken.');
                    }
                },
            ],
            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'regex:/^(?:\+9639|09|009639)\d{8}$/',
                function (string $attribute, mixed $value, \Closure $fail) use ($pharmacistId) {
                    $hash = User::hashForLookup(User::normalizePhone($value));
                    $existing = User::where('phone_hash', $hash)
                        ->where('id', '!=', $pharmacistId)
                        ->exists();
                    if ($existing) {
                        $fail('The phone number has already been taken.');
                    }
                },
            ],
        ];
    }
}

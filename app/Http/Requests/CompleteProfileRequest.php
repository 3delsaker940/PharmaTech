<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'father_name' => ['nullable', 'sometimes', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => [
                'required',
                'string',
                'regex:/^(?:\+9639|09|009639)\d{8}$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $hash = User::hashForLookup(User::normalizePhone($value));
                    $existing = User::where('phone_hash', $hash)
                        ->where('id', '!=', $this->user()->id)
                        ->exists();
                    if ($existing) {
                        $fail('The phone number has already been taken.');
                    }
                },
            ],
            'pharmacy_name' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:255', 'sometimes'],
        ];
    }
}

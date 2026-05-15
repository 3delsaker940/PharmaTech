<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\City;

class LoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pharmacy = $this->pharmacies->first();
        $city = City::findOrFail($pharmacy->city_id);
        $governorate_id = $city->governorate_id;
        return [
            'message' => 'User logged in successfully',
            'user' => [
                'first_name' => $this->first_name,
                'father_name' => $this->father_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone_number' => $this->phone_number,
                'access_token' => $this->accessToken,
                'refresh_token' => $this->refreshToken,
                'token_type' => 'Bearer',
            ],
            'pharmacy' => [
                'name' => $pharmacy->name ?? null,
                'governorate_id' => $governorate_id ?? null,
                'city_id' => $pharmacy->city_id ?? null,
                'address' => $pharmacy->address ?? null,
            ],
        ];
    }
}

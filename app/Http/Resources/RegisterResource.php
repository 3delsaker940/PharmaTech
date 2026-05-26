<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\City;

class RegisterResource extends JsonResource
{
    protected $accessToken;
    protected $refreshToken;

    public function __construct($resource, $accessToken = null, $refreshToken = null)
    {
        parent::__construct($resource);
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function toArray(Request $request): array
    {
        $pharmacy = $this->pharmacies->first();

        return [
            'message' => 'User registered successfully. Please check your email for verification link.',
            'user' => [
                'id' => $this->id,
                'first_name' => $this->first_name,
                'father_name' => $this->father_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone_number' => $this->phone_number,
                'licence_number' => $this->licence_number,
            ],
            'pharmacy' =>$pharmacy ? [
                'id' => $pharmacy->id,
                'name' => $pharmacy->name,
                'governorate_id' => $pharmacy->city?->governorate_id,
                'city_id' => $pharmacy->city_id,
                'address' => $pharmacy->address,
            ]: null,
            'token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => 'Bearer',
        ];
    }
}

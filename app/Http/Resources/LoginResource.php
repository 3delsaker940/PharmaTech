<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
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
    $pharmacy = $this->pharmacy;

    return [
        'message' => 'User logged in successfully',

        'user' => [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'father_name' => $this->father_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'avatar'         => $this->avatar,
            'is_verified'    => (bool) $this->email_verified_at,
        ],

        'pharmacy' => $pharmacy ? [
            'id' => $pharmacy->id,
            'name' => $pharmacy->name,
            'governorate_id' => $pharmacy->city?->governorate_id,
            'city_id' => $pharmacy->city_id,
            'address' => $pharmacy->address,
            'license_number' => $pharmacy->license_number,
            'status'         => $pharmacy->status,
        ] : null,

        'access_token' => $this->accessToken,
        'refresh_token' => $this->refreshToken,
        'token_type' => 'Bearer',
    ];
}
}

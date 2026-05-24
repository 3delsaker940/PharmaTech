<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\City;

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
    $pharmacy = $this->pharmacies->first();

    $city = $pharmacy
        ? City::find($pharmacy->city_id)
        : null;

    $governorate_id = $city?->governorate_id;

    return [
        'message' => 'User logged in successfully',

        'user' => [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'father_name' => $this->father_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'licence_number' => $this->licence_number,
        ],

        'pharmacy' => $pharmacy ? [
            'name' => $pharmacy->name,
            'governorate_id' => $governorate_id,
            'city_id' => $pharmacy->city_id,
            'address' => $pharmacy->address,
        ] : null,

        'access_token' => $this->accessToken,
        'refresh_token' => $this->refreshToken,
        'token_type' => 'Bearer',
    ];
}
}

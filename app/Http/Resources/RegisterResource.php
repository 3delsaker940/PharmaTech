<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'message' => 'User registered successfully. Please check your email for verification link.',
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'licence_number' => $this->licence_number,
            'token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => 'Bearer',
        ];
    }
}

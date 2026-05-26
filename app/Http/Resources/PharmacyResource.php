<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'address'        => $this->address,
            'phone_number'   => $this->phone_number,
            'license_number' => $this->license_number,
            'status'         => $this->status,
            'city'           => new CityResource($this->whenLoaded('city')),
            'created_at'     => $this->created_at,
        ];
    }
}

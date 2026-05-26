<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'membership_role' => $this->membership_role,
            'status'          => $this->status,
            'joined_at'       => $this->joined_at,
            'user'            => new UserResource($this->whenLoaded('user')),
            'invited_by'      => new UserResource($this->whenLoaded('invitedBy')),
        ];
    }
}

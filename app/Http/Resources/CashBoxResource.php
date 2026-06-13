<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashBoxResource extends JsonResource
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
            'name'            => $this->name,
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->current_balance,
            'status'          => $this->status,
            'opened_at'       => $this->opened_at,
            'closed_at'       => $this->closed_at,

            'opened_by' => new UserResource($this->whenLoaded('openedBy')),

            'closed_by' => $this->closedBy
                ? new UserResource($this->closedBy)
                : null,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

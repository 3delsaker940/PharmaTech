<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'transaction_type' => $this->transaction_type,
            'amount'           => $this->amount,
            'reference_type'   => $this->reference_type,
            'reference_id'     => $this->reference_id,
            'notes'            => $this->notes,
            'transaction_time' => $this->transaction_time,

            'created_by' => new UserResource($this->whenLoaded('createdBy')),

            'created_at' => $this->created_at,
        ];
    }
}

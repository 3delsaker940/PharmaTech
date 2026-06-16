<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierDebtPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'cash_transaction_id' => $this->cash_transaction_id,
            'amount'              => $this->amount,
            'payment_date'        => $this->payment_date,
            'notes'               => $this->notes,

            'created_by' => new UserResource($this->whenLoaded('createdBy')),

            'created_at' => $this->created_at,
        ];
    }
}

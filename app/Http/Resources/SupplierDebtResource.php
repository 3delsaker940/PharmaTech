<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierDebtResource extends JsonResource
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
            'supplier_id'         => $this->supplier_id,
            'purchase_invoice_id' => $this->purchase_invoice_id,
            'total_amount'        => $this->total_amount,
            'paid_amount'         => $this->paid_amount,
            'remaining_amount'    => $this->remaining_amount,
            'due_date'            => $this->due_date,
            'status'              => $this->status,

            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'payments' => SupplierDebtPaymentResource::collection($this->whenLoaded('payments')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

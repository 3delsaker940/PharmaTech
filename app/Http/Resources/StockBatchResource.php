<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockBatchResource extends JsonResource
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
            'product_id'          => $this->product_id,
            'purchase_invoice_id' => $this->purchase_invoice_id,
            'batch_number'        => $this->batch_number,
            'expiry_date'         => $this->expiry_date,
            'purchase_price'      => $this->purchase_price,
            'selling_price'       => $this->selling_price,
            'quantity_on_hand'    => $this->quantity_on_hand,
            'received_at'         => $this->received_at,
            'status'              => $this->status,

            'product' => new ProductResource($this->whenLoaded('product')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

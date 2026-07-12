<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'product_id'=> $this->product_id,
            'quantity' => $this->quantity,
            'selling_price' => $this->selling_price,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'line_total' => $this->line_total,
            'product' => $this->product
                ? new ProductResource($this->product)
                : null,
            'created_at' => $this->created_at,
        ];
    }
}

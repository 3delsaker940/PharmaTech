<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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
            'product_id'      => $this->product_id,
            'movement_type'   => $this->movement_type,
            'quantity_change' => $this->quantity_change,
            'reference_type'  => $this->reference_type,
            'reference_id'    => $this->reference_id,
            'notes'           => $this->notes,

            'product'    => new ProductResource($this->whenLoaded('product')),
            'created_by' => new UserResource($this->whenLoaded('createdBy')),

            'batch' => $this->batch
                ? new StockBatchResource($this->batch)
                : null,

            'created_at' => $this->created_at,
        ];
    }
}

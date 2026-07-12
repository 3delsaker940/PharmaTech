<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerReturnInvoiceResource extends JsonResource
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
            'invoice_number'=> $this->invoice_number,
            'invoice_date'=> $this->invoice_date,
            'subtotal'=> $this->subtotal,
            'tax_total'=> $this->tax_total,
            'discount_total'=> $this->discount_total,
            'refund_total'=> $this->refund_total,
            'refund_method'=> $this->refund_method,
            'status'=> $this->status,
            'reason'=> $this->reason,
            'notes'=> $this->notes,
            'customer' => $this->customer
                ? new CustomerResource($this->customer)
                : null,
            'original_sales_invoice_id' => $this->original_sales_invoice_id,
            'items' => CustomerReturnItemResource::collection(
                $this->whenLoaded('items')
            ),
            'created_by' => $this->createdBy
                ? new UserResource($this->createdBy)
                : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

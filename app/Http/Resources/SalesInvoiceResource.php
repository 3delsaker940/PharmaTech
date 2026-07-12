<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number'=> $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'discount_total' => $this->discount_total,
            'grand_total'=> $this->grand_total,
            'amount_paid' => $this->amount_paid,
            'amount_due'=> $this->amount_due,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status'=> $this->status,
            'notes' => $this->notes,
            'customer' => $this->customer
                ? new CustomerResource($this->customer)
                : null,
            'items' => SalesInvoiceItemResource::collection(
                $this->whenLoaded('items')
            ),
            'customer_debt' => $this->customerDebt
                ? new CustomerDebtResource($this->customerDebt)
                : null,
            'created_by' => $this->createdBy
                ? new UserResource($this->createdBy)
                : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

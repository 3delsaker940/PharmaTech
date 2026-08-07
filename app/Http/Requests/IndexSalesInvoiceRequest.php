<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'         => 'nullable|string|in:completed,cancelled',
            'payment_status' => 'nullable|string|in:paid,unpaid,partial',
            'payment_method' => 'nullable|string',
            'walk_in'        => 'nullable|boolean',
            'customer_id'    => [
                'nullable',
                'integer',
                'exists:customers,id',
                'prohibited_if:walk_in,true,1', 
            ],
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date|after_or_equal:date_from',
            'per_page'       => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.prohibited_if' => 'Cannot specify a customer_id when walk_in is set to true.',
        ];
    }
}

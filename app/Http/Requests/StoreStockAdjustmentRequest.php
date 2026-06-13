<?php

namespace App\Http\Requests;

use App\Models\StockBatch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pharmacyId = $this->attributes->get('pharmacy')->id;

        return [
            'adjustment_type' => ['required', 'in:add,remove'],

            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('pharmacy_id', $pharmacyId)
                    ->where('deleted_at', null),
            ],

            'quantity' => ['required', 'integer', 'min:1'],

            'purchase_price' => ['required_if:adjustment_type,add', 'numeric', 'min:0'],
            'selling_price'  => ['required_if:adjustment_type,add', 'numeric', 'min:0'],
            'batch_number'   => ['nullable', 'string', 'max:255'],
            'expiry_date'    => ['nullable', 'date'],

            'batch_id' => [
                'required_if:adjustment_type,remove',
                'integer',
                Rule::exists('stock_batches', 'id')->where('pharmacy_id', $pharmacyId),
            ],

            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_price.required_if' => 'Purchase price is required when adding stock.',
            'selling_price.required_if'  => 'Selling price is required when adding stock.',
            'batch_id.required_if'       => 'Batch is required when removing stock.',
            'batch_id.exists'            => 'The selected batch does not exist in this pharmacy.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('adjustment_type') !== 'remove') {
                return;
            }

            $batchId   = $this->input('batch_id');
            $productId = $this->input('product_id');
            $quantity  = (int) $this->input('quantity', 0);

            if (! $batchId || ! $productId) {
                return;
            }

            $batch = StockBatch::find($batchId);

            if (! $batch) {
                return;
            }

            if ((int) $batch->product_id !== (int) $productId) {
                $validator->errors()->add(
                    'batch_id',
                    'The selected batch does not belong to the selected product.'
                );

                return;
            }

            if ($batch->quantity_on_hand < $quantity) {
                $validator->errors()->add(
                    'quantity',
                    "Cannot remove {$quantity} units. Only {$batch->quantity_on_hand} available in batch {$batch->batch_number}."
                );
            }
        });
    }
}

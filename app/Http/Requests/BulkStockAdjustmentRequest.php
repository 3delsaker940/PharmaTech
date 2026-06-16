<?php

namespace App\Http\Requests;

use App\Models\StockBatch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkStockAdjustmentRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],

            'items.*.adjustment_type' => ['required', 'in:add,remove'],

            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('pharmacy_id', $pharmacyId)
                    ->where('deleted_at', null),
            ],

            'items.*.quantity' => ['required', 'integer', 'min:1'],

            'items.*.purchase_price' => ['required_if:items.*.adjustment_type,add', 'numeric', 'min:0'],
            'items.*.selling_price'  => ['required_if:items.*.adjustment_type,add', 'numeric', 'min:0'],
            'items.*.batch_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stock_batches', 'batch_number')
                    ->where('pharmacy_id', $pharmacyId),
            ],
            'items.*.expiry_date'    => ['nullable', 'date'],

            'items.*.batch_id' => [
                'required_if:items.*.adjustment_type,remove',
                'integer',
                Rule::exists('stock_batches', 'id')->where('pharmacy_id', $pharmacyId),
            ],

            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                     => 'At least one adjustment item is required.',
            'items.*.purchase_price.required_if' => 'Purchase price is required when adding stock.',
            'items.*.selling_price.required_if'  => 'Selling price is required when adding stock.',
            'items.*.batch_id.required_if'       => 'Batch is required when removing stock.',
            'items.*.batch_id.exists'            => 'The selected batch does not exist in this pharmacy.',
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                if (($item['adjustment_type'] ?? null) !== 'remove') {
                    continue;
                }

                $batchId   = $item['batch_id'] ?? null;
                $productId = $item['product_id'] ?? null;
                $quantity  = (int) ($item['quantity'] ?? 0);

                if (! $batchId || ! $productId) {
                    continue;
                }

                $batch = StockBatch::find($batchId);

                if (! $batch) {
                    continue;
                }

                if ((int) $batch->product_id !== (int) $productId) {
                    $validator->errors()->add(
                        "items.$index.batch_id",
                        'The selected batch does not belong to the selected product.'
                    );

                    continue;
                }

                if ($batch->quantity_on_hand < $quantity) {
                    $validator->errors()->add(
                        "items.$index.quantity",
                        "Cannot remove {$quantity} units. Only {$batch->quantity_on_hand} available in batch {$batch->batch_number}."
                    );
                }
            }
            $batchNumbers = collect($this->input('items', []))
                ->pluck('batch_number')
                ->filter()
                ->values();

            if ($batchNumbers->count() !== $batchNumbers->unique()->count()) {
                $duplicates = $batchNumbers
                    ->duplicates()
                    ->unique()
                    ->values()
                    ->implode(', ');

                $validator->errors()->add(
                    'items',
                    "Duplicate batch numbers found within the same bulk request: {$duplicates}."
                );
            }
        });
    }
}

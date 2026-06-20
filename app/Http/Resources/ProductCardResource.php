<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalQuantity = (int) $this->total_quantity;
        $minStock = (int) $this->min_stock;

        return [
            'id' => $this->id,
            'brand_name' => $this->brand_name,
            'selling_price' => $this->selling_price,
            'base_unit' => $this->base_unit,
            'min_stock' => $this->min_stock,
            'total_quantity' => $totalQuantity,
            'nearest_expiry' => $this->nearest_expiry,
            'stock_status' => $this->resolveStockStatus($totalQuantity, $minStock),
            'stock_alert_severity' => $this->resolveSeverity($totalQuantity, $minStock),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }

    private function resolveStockStatus(int $totalQuantity, int $minStock): string
    {
        if ($totalQuantity === 0) {
            return 'out';
        }
        if ($totalQuantity < $minStock) {
            return 'low';
        }
        return 'available';
    }
    private function resolveSeverity(int $totalQuantity, int $minStock): string
    {
        if ($totalQuantity === 0) {
            return 'out';
        }
        if ($totalQuantity <= $minStock * 0.25) {
            return 'critical';
        }
        return 'low';
    }
}

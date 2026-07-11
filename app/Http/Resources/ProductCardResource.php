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
            'ar_name' => $this->ar_name,
            'strength' => $this->strength,
            'selling_price' => $this->selling_price,
            'min_stock' => $this->min_stock,
            'total_quantity' => $totalQuantity,
            'nearest_expiry' => $this->nearest_expiry,
            'stock_status' => $this->resolveStockStatus($totalQuantity, $minStock),
            'stock_alert_severity' => $this->resolveSeverity($totalQuantity, $minStock),
            'base_unit' => $this->baseUnit
                ? new UnitResource($this->baseUnit)
                : null,
            'selling_unit' => $this->sellingUnit
                ? new UnitResource($this->sellingUnit)
                : null,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'company' => $this->company
                ? new CompanyResource($this->company)
                : null,
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

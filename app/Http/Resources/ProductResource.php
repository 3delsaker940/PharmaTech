<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'barcode' => $this->barcode,
            'brand_name' => $this->brand_name,
            'scientific_name' => $this->scientific_name,
            'ar_name' => $this->ar_name,
            'strength' => $this->strength,
            'prescription_required' => $this->prescription_required,
            'buying_price' => $this->buying_price,
            'selling_price' => $this->selling_price,
            'total_quantity' => $this->total_quantity,
            'tax_rate' => $this->tax_rate,
            'discount_rate' => $this->discount_rate,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'units_per_base' => $this->units_per_base,
            'allow_partial_selling' => $this->allow_partial_selling,
            'nearest_expiry' => $this->nearest_expiry,
            'shelf' => $this->shelf,
            'image_path'=>$this->image_path,
            'deleted_at' => $this->deleted_at,
            'base_unit' => $this->baseUnit
                ? new UnitResource($this->baseUnit)
                : null,
            'selling_unit' => $this->sellingUnit
                ? new UnitResource($this->sellingUnit)
                : null,
            'category'=> new CategoryResource($this->whenLoaded('category')),
            'company' => $this->company
                ? new CompanyResource($this->company)
                : null,
            'medical_info' => new ProductMedicalInfoResource($this->whenLoaded('medicalInfo')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

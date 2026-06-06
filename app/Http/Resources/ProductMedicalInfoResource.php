<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMedicalInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'indications'       => $this->indications,
            'contraindications' => $this->contraindications,
            'overdose'          => $this->overdose,
            'pregnancy_safety'  => $this->pregnancy_safety,
            'lactation_safety'  => $this->lactation_safety,
            'warnings'          => $this->warnings,
            'side_effects'      => $this->side_effects,
            'drug_interactions' => $this->drug_interactions,
            'dose_info'         => $this->dose_info,
            'updated_at'        => $this->updated_at,
        ];
    }
}

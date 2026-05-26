<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;

trait AuthorizesPharmacyResource
{
    /**
     * Abort 403 if the given resource pharmacy_id doesn't match
     * the pharmacy resolved by the ResolvePharmacy middleware.
     */
    protected function authorizePharmacyResource(Request $request, int $resourcePharmacyId): void
    {
        $pharmacy = $request->attributes->get('pharmacy');

        abort_if(
            $pharmacy->id !== $resourcePharmacyId,
            403,
            'This resource does not belong to the current pharmacy context.'
        );
    }
}

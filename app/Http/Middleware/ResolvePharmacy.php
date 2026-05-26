<?php

namespace App\Http\Middleware;

use App\Models\Pharmacy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePharmacy
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pharmacyId = $request->header('X-Pharmacy-ID');

        if (! $pharmacyId || ! ctype_digit((string) $pharmacyId)) {
            return response()->json([
                'message' => 'Missing or invalid X-Pharmacy-ID header.',
            ], 422);
        }

        $pharmacy = Pharmacy::find((int) $pharmacyId);

        if (! $pharmacy) {
            return response()->json(['message' => 'Pharmacy not found.'], 404);
        }

        if (! $request->user()->hasPharmacyAccess($pharmacy->id)) {
            return response()->json([
                'message' => 'You do not have access to this pharmacy.',
            ], 403);
        }

        $request->attributes->set('pharmacy', $pharmacy);
        return $next($request);
    }
}

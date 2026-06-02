<?php

namespace App\Http\Middleware;

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
        /** @var \App\Models\User $user */
        $user = $request->user();
        $pharmacy = $user->pharmacy;

        if (! $pharmacy) {
            return response()->json([
                'message' => 'No pharmacy associated with your account.',
            ], 403);
        }

        $request->attributes->set('pharmacy', $pharmacy);
        return $next($request);
    }
}

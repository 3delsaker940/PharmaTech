<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $units = Unit::query()
            ->when(
                request()->filled('type'),
                fn ($q) => $q->where('type', request()->input('type'))
            )
            ->orderBy('type')
            ->orderBy('name')
            ->paginate((int) $request->input('per_page', 50));

        return UnitResource::collection($units);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $companies = Company::query()
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('name', 'like', '%' . $request->input('search') . '%')
            )
            ->orderBy('name')
            ->get();
        return CompanyResource::collection($companies);
    }
    public function show(Company $company): CompanyResource
    {
        return new CompanyResource($company);
    }
}

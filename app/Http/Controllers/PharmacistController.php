<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePharmacistRequest;
use App\Http\Requests\UpdatePharmacistRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PharmacistController extends Controller
{
    /**
     * Display all pharmacists belonging to the current pharmacy owner.
     */
    public function index(Request $request)
    {
        $pharmacists = User::role('pharmacist')
            ->where('pharmacy_id', $request->user()->pharmacy_id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $pharmacists,
        ], 200);
    }

    /**
     * Store a new pharmacist.
     */
    public function store(StorePharmacistRequest $request)
    {
        $validated = $request->validated();

        try {
            $pharmacist = DB::transaction(function () use ($validated, $request) {
                $randomPassword = Str::random(10);

                $user = User::create([
                    'first_name' => $validated['first_name'],
                    'father_name' => $validated['father_name'] ?? null,
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'phone_number' => $validated['phone_number'],
                    'pharmacy_id' => $request->user()->pharmacy_id,
                    'password' => Hash::make($randomPassword),
                ]);

                $user->assignRole('pharmacist');

                // Store plain password temporarily in Cache for 7 days
                Cache::put("temp_password_{$user->id}", $randomPassword, now()->addDays(7));

                $user->sendEmailVerificationNotification();

                return $user;
            });

            return response()->json([
                'message' => 'Pharmacist created successfully. Verification email sent.',
                'data' => $pharmacist,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to store pharmacist: '.$e->getMessage());

            return response()->json([
                'message' => 'An error occurred while creating the pharmacist.',
            ], 500);
        }
    }

    /**
     * Display details of a specific pharmacist.
     */
    public function show(Request $request, $id)
    {
        $pharmacist = User::role('pharmacist')
            ->where('pharmacy_id', $request->user()->pharmacy_id)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $pharmacist,
        ], 200);
    }

    public function update(UpdatePharmacistRequest $request, $id)
    {
        try {
            $pharmacist = User::role('pharmacist')
                ->where('pharmacy_id', $request->user()->pharmacy_id)
                ->findOrFail($id);

            $pharmacist->fill($request->validated());

            if ($pharmacist->isDirty()) {
                $pharmacist->save();
            }

            return response()->json([
                'message' => 'Pharmacist updated successfully.',
                'data' => $pharmacist->fresh(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update pharmacist: '.$e->getMessage());

            return response()->json([
                'message' => 'An error occurred while updating the pharmacist.',
            ], 500);
        }
    }

    /**
     * Delete a pharmacist.
     */
    public function destroy(Request $request, $id)
    {
        $pharmacist = User::role('pharmacist')
            ->where('pharmacy_id', $request->user()->pharmacy_id)
            ->findOrFail($id);

        $pharmacist->delete();

        return response()->json([
            'message' => 'Pharmacist deleted successfully.',
        ], 200);
    }
}

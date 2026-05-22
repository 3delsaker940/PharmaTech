<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Models\Governorate;
use App\Http\Resources\GovernorateResource;
use App\Http\Resources\CityResource;
use App\Models\City;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:3,1');

Route::post('/refresh', [AuthController::class, 'refresh']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

Route::post('/logout-all', [AuthController::class, 'logoutAll'])
    ->middleware('auth:sanctum');
Route::get('/governorates', function () {
    $governorates = Governorate::all();
    return GovernorateResource::collection($governorates);
});
Route::get('/cities', function () {
    $cities = City::all();
    return CityResource::collection($cities);
});
Route::get('/governorates-cities', function () {
    $governorates = Governorate::with('cities')->get();
    return GovernorateResource::collection($governorates);
});

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('verification.verify');

Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])
    ->middleware(['throttle:1,1'])
    ->name('verification.send');

// Password reset routes
Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:1,1')
    ->name('password.forgot');

Route::get('/password/reset', [AuthController::class, 'redirectToApp'])
    ->middleware('signed')
    ->name('password.reset');

Route::post('/password/reset', [AuthController::class, 'resetPassword']);
//sign in with google
Route::post('/auth/google', [AuthController::class, 'googleLogin']);
Route::post('auth/google/complete-profile', [AuthController::class, 'completeProfile'])->middleware('auth:sanctum');

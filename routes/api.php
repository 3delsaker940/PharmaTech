<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMedicalInfoController;
use App\Http\Controllers\SupplierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
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


Route::prefix('pharmacy')
    ->middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('/products', [InventoryController::class, 'getAllPharmacyProducts']);
        Route::get('/{productId}/stock-batches', [InventoryController::class, 'getProductStockBatches']);
        Route::get('/category/{categoryId}/products', [InventoryController::class, 'getProductsByCategory']);
    });

Route::prefix('categories')
    ->middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('',                         [CategoryController::class, 'index']);
        Route::post('',                        [CategoryController::class, 'store']);
        Route::get('{category}',              [CategoryController::class, 'show']);
        Route::put('{category}',              [CategoryController::class, 'update']);
        Route::delete('{category}',        [CategoryController::class, 'destroy']);
        Route::patch('{category}/restore', [CategoryController::class, 'restore'])->withTrashed();
    });

Route::prefix('products')
    ->middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('barcode/{barcode}',         [ProductController::class, 'lookupByBarcode']);
        Route::get('',                           [ProductController::class, 'index']);
        Route::post('',                          [ProductController::class, 'store']);
        Route::get('{product}',                 [ProductController::class, 'show']);
        Route::put('{product}',                 [ProductController::class, 'update']);
        Route::delete('{product}',              [ProductController::class, 'destroy']);
        Route::patch('{product}/restore',       [ProductController::class, 'restore'])->withTrashed();

        Route::get('/{product}/medical-info',    [ProductMedicalInfoController::class, 'show']);
        Route::put('{product}/medical-info',    [ProductMedicalInfoController::class, 'upsert']);
        Route::delete('{product}/medical-info', [ProductMedicalInfoController::class, 'destroy']);
    });

Route::prefix('suppliers')
    ->middleware(['auth:sanctum', 'resolve.pharmacy'])
    ->group(function () {
        Route::get('',                          [SupplierController::class, 'index']);
        Route::post('',                         [SupplierController::class, 'store']);
        Route::get('{supplier}',               [SupplierController::class, 'show']);
        Route::put('{supplier}',               [SupplierController::class, 'update']);
        Route::delete('{supplier}',        [SupplierController::class, 'destroy']);
        Route::patch('{supplier}/restore', [SupplierController::class, 'restore'])->withTrashed();
    });

<?php


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/logout-all', [AuthController::class, 'logoutAll'])->middleware('auth:sanctum');

Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {
    $user = User::findOrFail($id);

    if (!hash_equals($hash, sha1($user->email))) {
        return response()->json(['message' => 'Invalid verification link'], 403);
    }
    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified'], 200);
    }
    $user->markEmailAsVerified();

    return response()->json(['message' => 'Email verified successfully'], 200);
})->middleware('signed')->name('verification.verify');

Route::post('/email/resend', function (Request $request) {
    $user = User::where('email', $request->email)->firstOrFail();
    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified'], 200);
    }
    $user->sendEmailVerificationNotification();
    return response()->json(['message' => 'Link sent']);
})->middleware(['throttle:6,1'])->name('verification.send');

//Password reset routes
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);



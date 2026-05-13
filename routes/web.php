<?php

use App\Http\Controllers\Auth\SocialLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//sign in with google
Route::get('auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->name('auth.socialite.redirect');
Route::get('auth/{provider}/callback', [SocialLoginController::class, 'callback'])->name('auth.socialite.callback');

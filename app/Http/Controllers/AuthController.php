<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function register(RegisterRequest $request) {
        // LOG
        $user = User::create([
            'email' => $request->email,
            'password' => $request->password
        ]);
         $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'User registered successfully. Please check your email for verification link.']);
    }
}

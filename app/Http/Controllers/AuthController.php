<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\CompleteRegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {

                Log::info('Attempting user registration', [
                    'email' => $request->email
                ]);
                $user = User::create([
                    'email' => $request->email,
                    'password' => $request->password
                ]);
                $user->sendEmailVerificationNotification();
                $token = $user->createToken('auth_token')->plainTextToken;
                Log::info('User registered successfully', ['user_id' => $user->id]);
                return response()->json([
                    'message' => 'User registered successfully. Please check your email for verification link.',
                    'token' => $token
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error occurred while registering user', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'An error occurred while registering the user.'
            ], 500);
        }
    }

    public function completeRegistration(CompleteRegistrationRequest $request)
    {
        $user = $request->user();
        $user->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number
        ]);
        return response()->json(['message' => 'Registration completed successfully'], 200);
    }
    public function login(Request $request)
    {
        Log::info('Attempting user login', [
            'email' => $request->email
        ]);
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'

            ]);
            if (!Auth::attempt($request->only('email', 'password'))) {
                Log::warning('User login failed - invalid credentials', [
                    'email' => $request->email
                ]);
                return response()->json([
                    'message' => 'Invalid email or password'
                ], 401);
            }
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                return response()->json([
                    'message' => 'Please verify your email first'
                ], 403);
            }

            Log::info('User logged in successfully', [
                'email' => $request->email
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'User logged in successfully',
                'token' => $token
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error during user login', [
                'error_message' => $e->getMessage(),
                'email' => $request->email
            ]);
            return response()->json([
                'message' => "Something went wrong, please try again later."
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'user logged out successfully',
        ], 200);
    }

    public function  logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'user logged out from all sessions successfully',
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        log::info('Attempting password reset', [
            'email' => $request->email
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                $user->tokens()->delete();
            }
        );
        log::info('Password reset attempt', [
            'status' => $status
        ]);
        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Your password has been reset!'], 200)
            : response()->json(['message' => __($status)], 400);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);
        $status = Password::sendResetLink($request->only('email'));
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent to your email!'], 200)
            : response()->json(['message' => __($status)], 400);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Resources\RegisterResource;
use App\Http\Resources\LoginResource;
use App\Models\User;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RefreshTokenService $refreshTokenService)
    {
        try {
            return DB::transaction(
                function () use ($request, $refreshTokenService) {
                    Log::info('Attempting user registration', [
                        'email' => $request->email,
                        'name' =>  $request->first_name . ' ' . $request->father_name . ' ' . $request->last_name
                    ]);
                    $user = User::create([
                        'email' => $request->email,
                        'password' => $request->password,
                        'first_name' => $request->first_name,
                        'father_name' => $request->father_name,
                        'last_name' => $request->last_name,
                        'phone_number' => $request->phone_number,
                        'licence_number' => $request->licence_number,
                    ]);
                    $user->pharmacies()->create([
                        'name' => $request->pharmacy_name,
                        // 'governorate_id' => $request->governorate_id,
                        'city_id' => $request->city_id,
                        'address' => $request->address,
                    ]);
                    $user->sendEmailVerificationNotification();
                    $accessToken  = $user->createToken('auth_token')->plainTextToken;
                    $refreshData  = $refreshTokenService->issue(
                        $user,
                        $request->input('device_name', 'auth_token'),
                        $request->ip(),
                        $request->userAgent()
                    );
                    Log::info('User registered successfully', ['user_id' => $user->id]);

                    $user->load('pharmacies');
                    return (new RegisterResource($user, $accessToken, $refreshData['refresh_token']))
                        ->response()
                        ->setStatusCode(201);
                }
            );
        } catch (\Exception $e) {
            Log::error('Error occurred while registering user', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'An error occurred while registering the user.'
            ], 500);
        }
    }

    public function login(LoginRequest  $request, RefreshTokenService $refreshTokenService)
    {
        Log::info('Attempting user login', [
            'email' => $request->email
        ]);
        try {
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
                $user->sendEmailVerificationNotification();
                return response()->json([
                    'message' => 'Please verify your email first. A new verification link has been sent to your email.'
                ], 403);
            }
            Log::info('User logged in successfully', [
                'email' => $request->email
            ]);

            $deviceName = $request->input('device_name', 'auth_token');

            $accessToken = $user->createToken($deviceName)->plainTextToken;

            $refreshData = $refreshTokenService->issue(
                $user,
                $deviceName,
                $request->ip(),
                $request->userAgent()
            );
            $user->accessToken = $accessToken;
            $user->refreshToken = $refreshData['refresh_token'];
            return (new LoginResource($user))
                ->response()
                ->setStatusCode(200);
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

    public function refresh(RefreshTokenRequest $request, RefreshTokenService $refreshTokenService)
    {
        try {
            $refreshData = $refreshTokenService->rotate(
                $request->refresh_token,
                $request->input('device_name'),
                $request->ip(),
                $request->userAgent()
            );

            /** @var \App\Models\User $user */
            $user = $refreshData['record']->user;

            $deviceName = $request->input('device_name', 'auth_token');

            $accessToken = $user->createToken($deviceName)->plainTextToken;

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshData['refresh_token'],
                'token_type' => 'Bearer',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Invalid or expired refresh token',
            ], 401);
        }
    }

    public function logout(Request $request, RefreshTokenService $refreshTokenService)
    {
        $request->user()->currentAccessToken()?->delete();

        if ($request->filled('refresh_token')) {
            $refreshTokenService->revokeByPlainToken($request->refresh_token);
        }

        return response()->json([
            'message' => 'user logged out successfully',
        ], 200);
    }

    public function  logoutAll(Request $request, RefreshTokenService $refreshTokenService)
    {
        $request->user()->tokens()->delete();
        $refreshTokenService->revokeAllForUser($request->user());

        return response()->json([
            'message' => 'user logged out from all sessions successfully',
        ], 200);
    }

    public function verifyEmail($id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals($hash, sha1($user->email))) {
            return response()->json(['message' => 'Invalid verification link'], 403);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
        $user->markEmailAsVerified();

        return redirect('pharmacyapp://email-verified?status=success');
    }

    public function resendVerificationEmail(Request $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
        $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'Link sent']);
    }

    public function resetPassword(ResetPasswordRequest $request, RefreshTokenService $refreshTokenService)
    {
        Log::info('Attempting password reset', [
            'email' => $request->email,
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($refreshTokenService) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
                $refreshTokenService->revokeAllForUser($user);
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
            'email' => 'required|email|exists:users,email',
        ]);
        $user = User::where('email', $request->email)->firstOrFail();
        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Please verify your email first.'], 403);
        }
        $status = Password::sendResetLink($request->only('email'));
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent to your email!'], 200)
            : response()->json(['message' => __($status)], 400);
    }

    public function redirectToApp(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        return redirect(
            'pharmacyapp://reset-password'
                . '?token=' . urlencode($token)
                . '&email=' . urlencode($email)
        );
    }
}

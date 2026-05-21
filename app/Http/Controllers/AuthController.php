<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteProfileRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Resources\RegisterResource;
use App\Http\Resources\LoginResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\RefreshTokenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Google\Client as GoogleClient;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const ACCESS_TOKEN_MINUTES = 15;

    private function issueTokenPair(
        User $user,
        Request $request,
        RefreshTokenService $refreshTokenService,
        ?string $deviceName = null
    ): array {
        $deviceName = $deviceName ?: $request->input('device_name', 'auth_token');

        $accessToken = $user->createToken(
            $deviceName,
            ['*'],
            now()->addMinutes(self::ACCESS_TOKEN_MINUTES)
        )->plainTextToken;

        $refreshData = $refreshTokenService->issue(
            $user,
            $deviceName,
            $request->ip(),
            $request->userAgent()
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshData['refresh_token'],
            'token_type' => 'Bearer',
            'device_name' => $deviceName,
        ];
    }

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
                    $tokens = $this->issueTokenPair(
                        $user,
                        $request,
                        $refreshTokenService
                    );
                    Log::info('User registered successfully', ['user_id' => $user->id]);

                    $user->load('pharmacies');
                    return (new RegisterResource(
                        $user,
                        $tokens['access_token'],
                        $tokens['refresh_token']
                    ))
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

            $tokens = $this->issueTokenPair(
                $user,
                $request,
                $refreshTokenService,
                $request->input('device_name', 'auth_token')
            );

            $user->load('pharmacies');

            return (new LoginResource(
                $user,
                $tokens['access_token'],
                $tokens['refresh_token']
            ))
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

            $accessToken = $user->createToken(
                $deviceName,
                ['*'],
                now()->addMinutes(self::ACCESS_TOKEN_MINUTES)
            )->plainTextToken;

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

    public function verifyEmail($id, $hash, Request $request)
    {
        $request->validate([
            'platform' => 'required|in:web,mobile'
        ]);
        $user = User::findOrFail($id);

        if (!hash_equals($hash, sha1($user->email))) {
            return response()->json(['message' => 'Invalid verification link'], 403);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
        $user->markEmailAsVerified();

        if ($request->query('platform') === 'web') {
            return redirect('http://localhost:5173/login/pharmacist' . '/email-verified?status=success');
        }
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
        $platform = $request->query('web', 'mobile');

        if ($platform === 'web') {
            return redirect(
                'http://localhost:5173/email-verify'
                    . '?token=' . urlencode($token)
                    . '&email=' . urlencode($email)
            );
        }

        return redirect(
            'pharmacyapp://reset-password'
                . '?token=' . urlencode($token)
                . '&email=' . urlencode($email)
        );
    }

    public function googleLogin(Request $request, RefreshTokenService $refreshTokenService)
    {
        try {
            $request->validate([
                'id_token' => 'required',
                'device_name' => 'nullable|string|max:255'
            ]);
            $client = new GoogleClient(['client_id' => env('GOOGLE_CLIENT_ID')]);
            $payload = $client->verifyIdToken($request->id_token);
            if (!$payload) {
                return response()->json([
                    'message' => 'Invalid Google token'
                ], 401);
            }
            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'] ?? null;
            $avatar = $payload['picture'] ?? null;
            $parts = $name ? explode(' ', $name, 2) : [];
            $firstName = $parts[0] ?? 'Google';
            $lastName = $parts[1] ?? 'User';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                ]
            );
            $isNewUser = $user->wasRecentlyCreated;
            if (!$isNewUser) {
                $user->forceFill([
                    'google_id' => $user->google_id ?: $googleId,
                    'avatar' => $avatar ?: $user->avatar,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ])->save();
            }

            $tokens = $this->issueTokenPair(
                $user,
                $request,
                $refreshTokenService,
                $request->input('device_name', 'google-login')
            );

            $user->load('pharmacies');
            return response()->json([
                'status' => true,
                'message' => $isNewUser ? 'Account created successfully' : 'Login successful',
                'data' => [
                    'user' => new UserResource($user),
                    'is_new_user' => $isNewUser,
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                ]
            ], 200);
        } catch (\Throwable  $e) {
            return response()->json([
                'message' => 'Something went wrong on the server',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function completeProfile(CompleteProfileRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = $request->user();
                $user->update([
                    'first_name' => $request->first_name,
                    'father_name' => $request->father_name,
                    'last_name' => $request->last_name,
                    'phone_number' => $request->phone_number,
                    'licence_number' => $request->licence_number
                ]);
                $user->pharmacies()->updateOrCreate([
                    'name' => $request->pharmacy_name,
                    'city_id' => $request->city_id,
                    'address' => $request->address,
                ]);
                //$accessToken = $request->bearerToken();
                Log::info('User registered successfully', ['user_id' => $user->id]);
                $user->load('pharmacies');
                return (new RegisterResource($user))
                    ->response()
                    ->setStatusCode(200);
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
}

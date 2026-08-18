<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordResetService
{
    protected RefreshTokenService $refreshTokenService;

    public function __construct(RefreshTokenService $refreshTokenService)
    {
        $this->refreshTokenService = $refreshTokenService;
    }

    /**
     * إرسال رابط استعادة كلمة المرور
     */
    public function sendResetLink(array $credentials): string
    {
        return Password::sendResetLink($credentials);
    }

    /**
     * إعادة تعيين كلمة المرور
     */
    public function resetPassword(array $credentials): string
    {
        return Password::reset(
            $credentials,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // تسجيل الخروج من جميع الجلسات بعد تغيير كلمة المرور
                $user->tokens()->delete();
                $this->refreshTokenService->revokeAllForUser($user);
            }
        );
    }

    /**
     * إنشاء رابط التوجيه للتطبيق أو الويب
     */
    public function getRedirectUrl(string $token, string $email, string $platform): string
    {
        $baseUrl = $platform === 'web' ? env('FRONTEND_WEB_RESET_URL') : env('FRONTEND_APP_RESET_URL');

        return $baseUrl . '?token=' . urlencode($token) . '&email=' . urlencode($email);
    }
}

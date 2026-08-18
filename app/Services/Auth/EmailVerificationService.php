<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;

class EmailVerificationService
{
    /**
     * Verify email and return redirect payload.
     */
    public function verifyEmail(int $id, string $hash, ?string $platform, ?string $timestamp): array
    {
        $platform = $platform ?: 'web';
        $user = User::findOrFail($id);

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return ['success' => false, 'message' => 'Invalid verification link', 'code' => 403];
        }

        $timestamp = $timestamp ?: (string) time();

        if ($user->hasVerifiedEmail()) {
            return [
                'success' => true,
                'redirect_url' => $this->getRedirectUrl($platform, 'already_verified', $user->email, $timestamp)
            ];
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return [
            'success' => true,
            'redirect_url' => $this->getRedirectUrl($platform, 'success', $user->email, $timestamp)
        ];
    }

    /**
     * Resend verification link.
     */
    public function resendLink(string $email): array
    {
        $user = User::where('email', $email)->firstOrFail();

        if ($user->hasVerifiedEmail()) {
            return ['success' => false, 'message' => 'Email already verified', 'code' => 200];
        }

        $user->sendEmailVerificationNotification();

        return ['success' => true, 'message' => 'Link sent'];
    }

    private function getRedirectUrl(string $platform, string $status, string $email, string $timestamp): string
    {
        $baseUrl = $platform === 'web' ? env('FRONTEND_WEB_VERIFIED_URL') : env('FRONTEND_APP_VERIFIED_URL');
        return $baseUrl . '?status=' . $status . '&email=' . urlencode($email) . '&t=' . $timestamp;
    }
}

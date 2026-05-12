<?php

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;

class RefreshTokenService
{
    public function issue(User $user, ?string $deviceName = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $plainRefreshToken = Str::random(80);

        $record = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainRefreshToken),
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => now()->addDays(30),
        ]);

        return [
            'refresh_token' => $plainRefreshToken,
            'record' => $record,
        ];
    }

    public function rotate(string $plainRefreshToken, ?string $deviceName = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $tokenHash = hash('sha256', $plainRefreshToken);

        $oldToken = RefreshToken::where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $user = $oldToken->user;

        $oldToken->forceFill([
            'revoked_at' => now(),
        ])->save();

        return $this->issue($user, $deviceName ?? $oldToken->device_name, $ipAddress, $userAgent);
    }

    public function revokeByPlainToken(string $plainRefreshToken): void
    {
        $tokenHash = hash('sha256', $plainRefreshToken);

        RefreshToken::where('token_hash', $tokenHash)->update([
            'revoked_at' => now(),
        ]);
    }

    public function revokeAllForUser(User $user): void
    {
        $user->refreshTokens()->update([
            'revoked_at' => now(),
        ]);
    }
}

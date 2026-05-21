<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        // Force Laravel to recognize the Ngrok domain and HTTPS protocol globally
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST']) || str_contains(config('app.url'), 'ngrok-free.dev')) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return URL::temporarySignedRoute(
                'password.reset',
                now()->addMinutes(60),
                [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                    'platform' => request()->input('platform', 'mobile')
                ]
            );
        });

        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                    'platform' => request()->input('platform', 'mobile')
                ]
            );
        });
    }
}

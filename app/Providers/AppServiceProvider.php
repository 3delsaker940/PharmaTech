<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\SupplierDebt;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\CustomerReturnInvoice;
use App\Models\SupplierReturnInvoice;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\AppNotification;

use App\Policies\ProductPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\CustomerDebtPolicy;
use App\Policies\SupplierDebtPolicy;
use App\Policies\PurchaseInvoicePolicy;
use App\Policies\SalesInvoicePolicy;
use App\Policies\CustomerReturnInvoicePolicy;
use App\Policies\SupplierReturnInvoicePolicy;
use App\Policies\StockBatchPolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\AppNotificationPolicy;

use Illuminate\Auth\Events\Verified;
use App\Listeners\SendPharmacistPasswordAfterVerification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use App\Auth\HashedEmailUserProvider;
use App\Models\User;
use App\Observers\UserObserver;

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
        User::observe(UserObserver::class);
        Auth::provider('hashed-eloquent', function ($app, array $config) {
            return new HashedEmailUserProvider($app['hash'], $config['model']);
        });

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerDebt::class, CustomerDebtPolicy::class);
        Gate::policy(SupplierDebt::class, SupplierDebtPolicy::class);
        Gate::policy(PurchaseInvoice::class, PurchaseInvoicePolicy::class);
        Gate::policy(SalesInvoice::class, SalesInvoicePolicy::class);
        Gate::policy(CustomerReturnInvoice::class, CustomerReturnInvoicePolicy::class);
        Gate::policy(SupplierReturnInvoice::class, SupplierReturnInvoicePolicy::class);
        Gate::policy(StockBatch::class, StockBatchPolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);
        Gate::policy(AppNotification::class, AppNotificationPolicy::class);

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
        Event::listen(
            Verified::class,
            SendPharmacistPasswordAfterVerification::class
        );
    }
}

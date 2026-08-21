<?php

namespace App\Observers;

use App\Models\User;
use App\Mail\UserStatusChangedMail;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }
    public function boot(): void
    {
        \App\Models\User::observe(\App\Observers\UserObserver::class);
    }

    /**
     * Handle the User "updated" event.
     */ public function updated(User $user): void
    {
        if ($user->wasChanged('status')) {
            Mail::to($user->email)->send(new UserStatusChangedMail($user));
            if ($user->status === 'inactive') {
                $user->tokens()->delete();
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}

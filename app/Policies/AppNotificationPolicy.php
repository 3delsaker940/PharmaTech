<?php

namespace App\Policies;

use App\Models\AppNotification;
use App\Models\User;

class AppNotificationPolicy
{
    public function view(User $user, AppNotification $appNotification): bool
    {
        return $user->id === $appNotification->user_id;
    }

    public function update(User $user, AppNotification $appNotification): bool
    {
        return $user->id === $appNotification->user_id;
    }
}

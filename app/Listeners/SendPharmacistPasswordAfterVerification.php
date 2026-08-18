<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;
use App\Mail\PharmacistPasswordMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendPharmacistPasswordAfterVerification
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        // فحص هل يوجد كلمة سر مؤقتة مخزنة لهذا المستخدم في الكاش
        $tempPassword = Cache::pull("temp_password_{$user->id}");

        if ($tempPassword) {
            Mail::to($user->email)->send(new PharmacistPasswordMail($tempPassword));
        }
    }
}

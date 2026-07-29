<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public function __construct(protected Messaging $messaging) {}

    public function sendAndSave(User $user, string $title, string $body, array $data = []): bool
    {
        AppNotification::create([
            'user_id' => $user->id,
            'title'   => $title,
            'body'    => $body,
            'data'    => json_encode($data),
        ]);

        if (!$user->fcm_token) return false;

        try {
            $message = CloudMessage::fromArray([
                'token' => $user->fcm_token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $data,
            ]);

            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            logger()->error('FCM Error: ' . $e->getMessage());
            return false;
        }
    }
}

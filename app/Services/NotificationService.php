<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Pharmacy;
use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

class NotificationService
{
    public function __construct(protected ?Messaging $messaging = null) {}

    public function sendAndSave(User $user, string $title, string $body, array $data = []): bool
    {
        // Save to the database first — this must succeed regardless of
        // whether Firebase is configured or reachable.
        AppNotification::create([
            'user_id' => $user->id,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);

        if (!$user->fcm_token || !$this->messaging) {
            return true;
        }

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
            // The database notification was already saved successfully.
            return true;
        }
    }

    /**
     * Send the same notification to every user belonging to a pharmacy.
     * This is the standard way to notify a pharmacy until a proper
     * roles/owner system exists — every user is currently equivalent.
     */
    public function sendToPharmacy(Pharmacy $pharmacy, string $title, string $body, array $data = []): void
    {
        $pharmacy->loadMissing('users');

        foreach ($pharmacy->users as $user) {
            $this->sendAndSave($user, $title, $body, $data);
        }
    }
}

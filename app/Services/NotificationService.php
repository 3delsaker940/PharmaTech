<?php

namespace App\Services ;
use App\Models\AppNotification;
use App\Models\Pharmacy;     
use App\Models\Product;
use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

class NotificationService
{
    private ?Messaging $messaging = null;

    public function __construct(?Messaging $messaging = null)
    {
        $this->messaging = $messaging;
    }

    public function sendAndSave(
        User $user,
        string $title,
        string $body,
        array $data = []
    ): bool {
        // Save notification in database
        AppNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        // No FCM token or Firebase service
        if (!$user->fcm_token || !$this->messaging) {
            return true;
        }

        try {
            $message = CloudMessage::fromArray([
                'token' => $user->fcm_token,

                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],

                'data' => $this->normalizeData($data),
            ]);

            $this->messaging->send($message);

            return true;
        } catch (\Throwable $e) {
            logger()->error('FCM notification error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Database notification was already saved.
            return true;
        }
    }

    public function sendToPharmacy(
        Pharmacy $pharmacy,
        string $title,
        string $body,
        array $data = []
    ): void {
        $users = $pharmacy->users()
            ->where('status', 'active')
            ->get();

        foreach ($users as $user) {
            $this->sendAndSave(
                $user,
                $title,
                $body,
                $data
            );
        }
    }

    private function normalizeData(array $data): array
    {
        return collect($data)
            ->map(fn($value) => (string) $value)
            ->toArray();
    }
}

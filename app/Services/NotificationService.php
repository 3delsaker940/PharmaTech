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

    /**
     * Persist a notification to the database AND push via FCM.
     *
     * Rules:
     *  - DB write failure is logged but never rethrows.
     *  - FCM failures are swallowed — \Throwable catches both \Exception and
     *    \Error (e.g. TypeError from kreait when data values are not strings).
     *  - ALL data values are cast to strings before being handed to FCM.
     */
    public function sendAndSave(User $user, string $title, string $body, array $data = []): bool
    {
        // ── 1. Persist to DB ───────────────────────────────────────────────
        try {
            AppNotification::create([
                'user_id' => $user->id,
                'title'   => $title,
                'body'    => $body,
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            logger()->error('[Notification] DB save failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            return false;
        }

        // ── 2. Push via FCM ────────────────────────────────────────────────
        if (!$user->fcm_token || !$this->messaging) {
            return true;
        }

        try {
            // kreait/firebase-php MessageData requires every value to be a
            // string — cast with array_map before building the message.
            $stringData = array_map('strval', $data);

            $message = CloudMessage::fromArray([
                'token'        => $user->fcm_token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $stringData,
            ]);

            $this->messaging->send($message);
        } catch (\Throwable $e) {
            // Catch \Throwable (not just \Exception) so TypeError from kreait
            // is also swallowed and never propagates to the caller.
            logger()->error('[Notification] FCM push failed: ' . $e->getMessage(), [
                'user_id'   => $user->id,
                'fcm_token' => substr($user->fcm_token ?? '', 0, 20) . '...',
            ]);
        }

        return true;
    }

    /**
     * Send the same notification to every user in a pharmacy.
     * Each user is independent — one failure never blocks others.
     */
    public function sendToPharmacy(Pharmacy $pharmacy, string $title, string $body, array $data = []): void
    {
        $pharmacy->loadMissing('users');

        foreach ($pharmacy->users as $user) {
            try {
                $this->sendAndSave($user, $title, $body, $data);
            } catch (\Throwable $e) {
                logger()->error('[Notification] sendToPharmacy loop error: ' . $e->getMessage(), [
                    'user_id'     => $user->id,
                    'pharmacy_id' => $pharmacy->id,
                ]);
            }
        }
    }
}

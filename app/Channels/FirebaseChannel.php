<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (!env('FIREBASE_PUSH_ENABLED', false)) {
            return;
        }

        $token = $notifiable->fcm_token;
        if (!$token) {
            return;
        }

        $message = null;
        if (method_exists($notification, 'toFirebase')) {
            $message = $notification->toFirebase($notifiable);
        } else {
            $message = $notification->message ?? 'Nuova notifica disponibile';
        }

        // Placeholder per logica reale di invio tramite FCM.
        Log::info("Invio notifica push Firebase al token {$token}", ['message' => is_string($message) ? $message : json_encode($message)]);
    }
}

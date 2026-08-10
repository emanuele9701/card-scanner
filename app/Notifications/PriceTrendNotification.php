<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceTrendNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $message;
    public $type;
    public $data;

    /**
     * Create a new notification instance.
     * $type: 'card' o 'set'
     */
    public function __construct(string $message, string $type, array $data)
    {
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (env('FIREBASE_PUSH_ENABLED', false) && !empty($notifiable->fcm_token)) {
            $channels[] = \App\Channels\FirebaseChannel::class;
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }

    public function toFirebase(object $notifiable)
    {
        return [
            'title' => 'Variazione Prezzo TCG',
            'body' => $this->message,
            'data' => $this->data
        ];
    }
}

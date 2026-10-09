<?php

namespace App\Notifications;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewFeedbackSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Feedback $feedback)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $student = $this->feedback->student;
        $guardian = $this->feedback->guardian;

        return [
            'feedback_id' => $this->feedback->id,
            'sender_name' => $student?->name ?? $guardian?->name ?? 'Pengguna',
            'sender_role' => $student ? 'siswa' : 'wali murid',
            'message' => $this->feedback->message,
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UpdateOrder extends Notification
{
    use Queueable;

    protected $user;

    /**
     * Create a new notification instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database']; // بدل mail إلى database
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'تم تحديث طلب جديد',
            'message' => 'قام المستخدم ' . $this->user->name . ' بتحديث طلبه.',
            'user_id' => $this->user->id,
            'time' => now()->format('Y-m-d H:i:s'),
        ];
    }
}

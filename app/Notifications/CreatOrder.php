<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CreatOrder extends Notification
{
     use Queueable;

    protected $user;
    protected $order;

    public function __construct($user, $order)
    {
        $this->user = $user;
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database']; // بدون إيميل
    }

    public function toArray($notifiable)
    {
        return [
            'title' => '🛍️ تم إنشاء طلب جديد',
            'message' => 'قام المستخدم ' . $this->user->name . ' بعمل طلبية جديدة رقم #' . $this->order->id . '، وسيتم مراجعتها قريبًا من قِبل الإدارة.',
            'order_id' => $this->order->id,
            'user_id' => $this->user->id,
        ];
    }
}

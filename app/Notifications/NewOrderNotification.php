<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
     use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database']; // بدون إيميل
    }

    public function toArray($notifiable)
    {
        return [
            'title' => '✅ تم إنشاء الطلبية بنجاح',
            'message' => 'تم استلام طلبك رقم #' . $this->order->id . ' وسيتم مراجعته قريبًا من قبل الإدارة.',
            'order_id' => $this->order->id,
        ];
    }
}

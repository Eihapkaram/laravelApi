<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupplierOrderCreated extends Notification
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $status
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'طلبية جديدة',
            'order_id' => $this->orderId,
            'message' => "لديك طلب تجهيز جديد رقم الطلب #{$this->orderId}",
            'status' => $this->status,
        ];
    }
}

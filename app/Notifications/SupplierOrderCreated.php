<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupplierOrderCreated extends Notification
{
    use Queueable;

    public function __construct(public $order) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'supplier_order_created',
            'order_id' => $this->order->id,
            'message' => 'لديك طلب تجهيز جديد',
            'status' => $this->order->status,
        ];
    }
}

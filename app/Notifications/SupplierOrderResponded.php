<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupplierOrderResponded extends Notification
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
            'type' => 'supplier_order_responded',
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => $this->order->status === 'preparing'
                ? 'المورد قبل طلب التجهيز'
                : 'المورد رفض طلب التجهيز',
            'reject_reason' => $this->order->supplier_reject_reason,
        ];
    }
}

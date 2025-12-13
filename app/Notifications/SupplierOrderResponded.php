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
        // رسالة حسب حالة الطلب
        $message = '';
        if ($this->order->status === 'preparing') {
            $message = 'المورد قبل طلب التجهيز';
        } elseif ($this->order->status === 'cancelled') {
            $reason = $this->order->supplier_reject_reason ?? 'بدون سبب محدد';
            $message = "المورد رفض طلب التجهيز. السبب: $reason";
        }

        return [
            'type' => 'supplier_order_responded',
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => $message,
            'reject_reason' => $this->order->supplier_reject_reason,
        ];
    }
}

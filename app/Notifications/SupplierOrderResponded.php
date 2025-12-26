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
        $supplier = $this->order->supplier;

        // رسالة حسب حالة الطلب
        if ($this->order->status === 'preparing') {
            $message = "{$supplier->name} قبل طلب التجهيز";
        } elseif ($this->order->status === 'cancelled') {
            $reason = $this->order->supplier_reject_reason ?? 'بدون سبب محدد';
            $message = "{$supplier->name} رفض طلب التجهيز. السبب: {$reason}";
        } else {
            $message = "تم تحديث حالة طلب {$supplier->name}";
        }

        return [
            'type' => 'supplier_order_responded',

            // بيانات الطلب
            'order_id' => $this->order->id,
            'status'   => $this->order->status,
            'message'  => $message,
            'reject_reason' => $this->order->supplier_reject_reason,

            // ✅ بيانات المورد
            'supplier' => [
                'id'    => $supplier->id,
                'name'  => $supplier->name,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
            ],

            // وقت الرد
            'responded_at' => $this->order->responded_at,
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupplierOrderStatusUpdated extends Notification
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'تحديث حالة طلب مورد',
            'message' => 'قام المورد '.$this->order->supplier->name.
                ' (ID: '.$this->order->supplier->id.') بتحديث حالة الطلب رقم #'.$this->order->id,

            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'supplier_name' => $this->order->supplier->name,
            'supplier_id' => $this->order->supplier->id,
        ];
    }
}

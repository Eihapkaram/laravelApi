<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderApprovedNotification extends Notification
{
    use Queueable;

    protected $order;
    protected $customer;

    public function __construct(Order $order, $customer)
    {
        $this->order = $order;
        $this->customer = $customer;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('تمت الموافقة على الطلب')
            ->line("قام {$this->customer->name} بالموافقة على الطلب رقم {$this->order->id}.")
            ->action('عرض الطلب', url("/orders/{$this->order->id}"))
            ->line('يمكنك الآن البدء في معالجة الطلب.');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "{$this->customer->name} وافق على الطلب رقم {$this->order->id}.",
            'order_id' => $this->order->id,
            'orderDetels' => $this->order->with('orderdetels.product')->get(),
        ];
    }
}

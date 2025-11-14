<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedBySellerNotification extends Notification
{
    use Queueable;

    protected $order;
    protected $seller;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, $seller)
    {
        $this->order = $order;
        $this->seller = $seller;
    }

    /**
     * Channels used: database + mail
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Message for email.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('طلب جديد من ' . $this->seller->name)
            ->greeting('مرحباً ' . $notifiable->name . ' 👋')
            ->line('قام المندوب ' . $this->seller->name . ' بإنشاء طلب جديد لك.')
            ->line('قيمة الطلب: ' . $this->order->total_price . ' جنيه.')
             ->line(' الرجاء تأكيد الطلبية ب ا لموافقة أو الرفض لكي يتم تاكيد الطلبية و شحنها لك ')
            ->action('عرض الطلب', url('/orders/' . $this->order->id));
           
    }

    /**
     * Message for database.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'order_created_by_seller',
            'message' => "قام {$this->seller->name} بإنشاء طلبية رقم{$this->order->id}# لك جديد بقيمة {$this->order->total_price}جنيه.من فضلك اكد الطلبيه من صفحة مشترياتك ليتم شحنها لك",
            'order_id' => $this->order->id,
            'seller_name' => $this->seller->name,
            'orderDetels' => $this->order->with('orderdetels.product')->get(),
        ];
    }
}

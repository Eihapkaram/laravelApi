<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Order;
use App\Models\User;

class NewOrderForSupplierNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $user;

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     * @param User $user
     */
    public function __construct(Order $order, User $user)
    {
        $this->order = $order;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'order_id'      => $this->order->id,
            'total_price'   => $this->order->total_price,
            'status'        => $this->order->status,
            'created_by'    => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'role'  => $this->user->role,
            ],
            // ✅ إضافة رقم الطلب داخل الرسالة
            'message'       => "تم إنشاء طلبية رقم {$this->order->id} لك من قبل {$this->user->name}.",
        ];
    }
}

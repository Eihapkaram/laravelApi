<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CartItemDeletedNotification extends Notification
{
    use Queueable;

    protected $user;

    protected $product;

    public function __construct($user, $product)
    {
        $this->user = $user;
        $this->product = $product;
    }

    // ✅ نوع الإشعار
    public function via($notifiable)
    {
        return ['database'];
    }

    // ✅ البيانات اللي هتتخزن في جدول notifications
    public function toDatabase($notifiable)
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'product_id' => $this->product->id,
            'product_name' => $this->product->titel,
            'message' => $this->user->name.' removed '.$this->product->titel.' from cart',
        ];
    }

    // optional fallback
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}

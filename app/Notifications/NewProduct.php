<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewProduct extends Notification
{
    use Queueable;

    protected $user;

    protected $product;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $product)
    {
        $this->user = $user;
        $this->product = $product;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // تخزين الإشعار في قاعدة البيانات
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🛍️ منتج جديد تمت إضافته')
            ->line("تم إضافة منتج جديد بواسطة {$this->user->name}.")
            ->line("اسم المنتج: {$this->product->titel}")
            ->line("السعر: {$this->product->price} جنيه")
            ->action('عرض المنتج', url("/products/{$this->product->id}"))
            ->line('شكراً لاستخدامك منصتنا!');
    }

    /**
     * تمثيل الإشعار في قاعدة البيانات.
     */
    public function toArray($notifiable)
    {
        return [
            'title' => '🛍️ منتج جديد تمت إضافته',
            'message' => "تمت إضافة المنتج '{$this->product->titel}' بواسطة تاجر البلد ",
            'product_id' => $this->product->id,
            'price' => $this->product->price,
        ];
    }
}

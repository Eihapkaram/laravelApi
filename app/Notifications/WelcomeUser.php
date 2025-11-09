<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WelcomeUser extends Notification
{
    use Queueable;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    // 📬 الإشعار فقط داخل قاعدة البيانات
    public function via($notifiable)
    {
        return ['database'];
    }

    // 💾 البيانات التي تُخزن في جدول notifications
    public function toArray($notifiable)
    {
        return [
            'title' => '🎉 مرحبًا بك في جُمَّلة الجُمَّلة!',
            'message' => 'أهلاً ' .  $this->user->name  . 'يلا ابدا دي جملة اسهل مكسب اكتر ابد شوف المنتجات واشتري ب الجملة ! شكرًا لانضمامك إلينا ❤️ ',
        ];
    }
}

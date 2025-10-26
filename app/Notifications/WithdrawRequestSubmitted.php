<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawRequestSubmitted extends Notification
{
    use Queueable;

    protected $amount;

    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    public function via($notifiable)
    {
        return ['database']; // 🔹 نحفظها في جدول notifications
    }

    public function toArray($notifiable)
    {
        return [
            'title' => '💰 تم إرسال طلب سحب الأرباح',
            'message' => "تم إرسال طلبك لسحب مبلغ {$this->amount} جنيه، وجارٍ المراجعة.",
        ];
    }
}

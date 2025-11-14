<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WithdrawRequestApproved extends Notification
{
    use Queueable;

    protected $amount;

    public function __construct($amount)
    {
        $this->amount = $amount;
    }

    public function via($notifiable)
    {
        return ['database']; // نحفظ الإشعار في جدول notifications
    }

    public function toArray($notifiable)
    {
        return [
            'title' => '✅ تم قبول طلب السحب',
            'message' => "تم قبول طلب سحب الأرباح بمبلغ {$this->amount} جنيه. سيتم تحويل المبلغ قريبا علي محفظتك الالكترونية.",
        ];
    }
}

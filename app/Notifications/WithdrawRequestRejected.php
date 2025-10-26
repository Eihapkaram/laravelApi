<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WithdrawRequestRejected extends Notification
{
    use Queueable;

    protected $amount;
    protected $note;

    public function __construct($amount, $note = null)
    {
        $this->amount = $amount;
        $this->note = $note;
    }

    public function via($notifiable)
    {
        return ['database']; // نحفظ الإشعار في جدول notifications
    }

    public function toArray($notifiable)
    {
        return [
            'title' => '❌ تم رفض طلب السحب',
            'message' => $this->note
                ? "تم رفض طلب سحب الأرباح بمبلغ {$this->amount} جنيه. السبب: {$this->note}"
                : "تم رفض طلب سحب الأرباح بمبلغ {$this->amount} جنيه.",
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewWithdrawRequest extends Notification
{
    use Queueable;

    protected $seller;
    protected $amount;

    public function __construct($seller, $amount)
    {
        $this->seller = $seller;
        $this->amount = $amount;
    }

    public function via($notifiable)
    {
        return ['database']; // تروح في جدول notifications
    }

    public function toArray($notifiable)
    {
        return [
            'title' => '📤 طلب سحب أرباح جديد',
            'message' => "البائع {$this->seller->name} طلب سحب مبلغ {$this->amount} ج.م",
            'seller_id' => $this->seller->id,
        ];
    }
}

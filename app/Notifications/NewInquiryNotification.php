<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Inquiry;

class NewInquiryNotification extends Notification
{
    use Queueable;

    protected $inquiry;

    /**
     * Create a new notification instance.
     */
    public function __construct(Inquiry $inquiry)
    {
        $this->inquiry = $inquiry;
    }

    /**
     * القنوات المستخدمة - قاعدة البيانات فقط
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * البيانات التي تُخزن في جدول الإشعارات
     */
    public function toDatabase($notifiable)
    {
        return [
            'inquiry_id' => $this->inquiry->id,
            'name' => $this->inquiry->name,
            'email' => $this->inquiry->email,
            'subject' => $this->inquiry->subject,
            'message' => 'استفسار جديد من العميل: ' . $this->inquiry->name,
        ];
    }
}

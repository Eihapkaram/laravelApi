<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Offer;

class OfferCreatedNotification extends Notification
{
    use Queueable;

    protected $offer;

    /**
     * Create a new notification instance.
     */
    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }

    /**
     * القنوات المستخدمة - قاعدة البيانات فقط
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * البيانات التي سيتم تخزينها في جدول الإشعارات
     */
    public function toDatabase($notifiable)
    {
        return [
            'offer_id' => $this->offer->id,
            'title' => $this->offer->title,
            'discount_value' => $this->offer->discount_value,
            'discount_type' => $this->offer->discount_type,
            'banner' => $this->offer->banner,
            'message' => 'تمت إضافة عرض جديد بعنوان: ' . $this->offer->title,
        ];
    }
}

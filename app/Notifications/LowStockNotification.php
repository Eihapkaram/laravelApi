<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Product;

class LowStockNotification extends Notification
{
    use Queueable;

    public $product;

    /**
     * Create a new notification instance.
     *
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database']; // database only
    }

    /**
     * Store the notification in the database.
     */
    public function toDatabase($notifiable)
    {
        return [
            'product_id' => $this->product->id,
            'titel'      => $this->product->titel,
            'stock'      => $this->product->stock,
            'message'    => "المنتج {$this->product->titel} وصل مخزونه إلى {$this->product->stock} فقط."
        ];
    }
}

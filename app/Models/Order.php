<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order_item;
use App\Models\User;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
         'seller_id',
        'status',
        'total_price',
        'shipping_address',
        'payment_method',
        'product_id',
        'city',
        'governorate',
        'street',
        'phone',
        'store_name',
        'approval_status',
        'approved_at',
        'store_banner',
    ];

    // ✅ تعريف العلاقة مع تفاصيل الطلب
    public function orderdetels()
    {
        return $this->hasMany(Order_item::class, 'order_id');
    }

    // ✅ تعريف العلاقة مع المستخدم
    public function userorder()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
     // ✅ البائع اللي أنشأ الطلب
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}

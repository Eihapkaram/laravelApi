<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cart;
use App\Models\product;
class Cart_item extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];

    // ✅ كل عنصر في السلة ينتمي إلى سلة معينة
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    // ✅ كل عنصر في السلة مرتبط بمنتج
    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }
}

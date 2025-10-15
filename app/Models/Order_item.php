<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\product;
use App\Models\Order;
class Order_item extends Model
{
    use HasFactory;
    public function product () {
        return $this->belongsTo(product::class,'product_id');
    }
    public function order () {
        return $this->belongsTo(Order::class,'order_id');
    }
    protected $fillable = ['product_id','quantity','price','order_id'];
}

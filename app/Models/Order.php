<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order_item;
use App\Models\User;

class Order extends Model
{
    protected $fillable = ['user_id','status','total_price','shipping_address','payment_method','product_id'];
    use HasFactory;
     public function orderdetels() {
       return $this->hasMany(Order_item::class);
    }
    public function userorder() {
       return $this->belongsTo(User::class);
    }
}

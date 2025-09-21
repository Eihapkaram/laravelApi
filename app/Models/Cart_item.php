<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cart;
use App\Models\product;

class Cart_item extends Model
{
    protected $fillable = ['product_id',
 'quantity'];
    use HasFactory;
public function cart()
{
    return $this->belongsTo(Cart::class);
}

public function product()
{
    return $this->belongsTo(Product::class);
}
}

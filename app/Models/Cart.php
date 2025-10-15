<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cart_item;
use App\Models\User;
class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    // ✅ كل سلة فيها عناصر
    public function proCItem()
    {
        return $this->hasMany(Cart_item::class, 'cart_id');
    }

    // ✅ كل سلة تخص مستخدم واحد
    public function getUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

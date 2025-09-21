<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cart_item;
use App\Models\categorie;
use App\Models\Order_item;
class product extends Model
{
     public function proditels() {
       return $this->hasMany(Cart_item::class);
    }
    public function categorie() {
        return $this->belongsTo(categorie::class);
    }
    public function orderdetils() {
       return $this->hasMany(Order_item::class);
    }
    protected $fillable = [
        'titel',
        'description',
        'votes',
        'url',
        'img',
        'updated_at',
        'created_at',
        'images_url',
        'price',
        'stock',
        'category_id'
    ];
    use HasFactory;
}

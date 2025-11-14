<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Cart_item;
use App\Models\categorie;
use App\Models\Order_item;
use App\Models\Image;
use App\Models\Review;
use App\Models\Page;

class Product extends Model
{
    use HasFactory;

    // ✅ الحقول المسموح بملئها
    protected $fillable = [
        'titel',
        'description',
        'votes',
        'brand',
        'page_id',
        'url',
        'img',
        'updated_at',
        'created_at',
        'images_url',
        'price',
        'stock',
        'category_id',
        'inCount',
        'Counttype',
        'inCounttype',
        'discount',
    ];

    protected $casts = [
        'images_url' => 'array', // Laravel يحول JSON لـ array تلقائيًا
    ];

    // ✅ المنتج له عناصر في السلة
    public function proditels()
    {
        return $this->hasMany(Cart_item::class, 'product_id');
    }

    // ✅ المنتج ينتمي لتصنيف (category)
    public function categorie()
    {
        return $this->belongsTo(categorie::class, 'category_id');
    }

    // ✅ المنتج ينتمي إلى صفحة
    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    // ✅ المنتج له عناصر في الطلبات
    public function orderdetils()
    {
        return $this->hasMany(Order_item::class, 'product_id');
    }

    // ✅ المنتج له صور متعددة
    public function images()
    {
        return $this->hasMany(Image::class, 'product_id');
    }

    // ✅ المنتج له مراجعات
    public function productReviwes()
    {
        return $this->hasMany(Review::class, 'product_id');
    }
}

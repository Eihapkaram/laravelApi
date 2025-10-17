<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'banner',
        'product_id',
        'discount_value',
        'discount_type',
        'start_date',
        'end_date',
        'is_active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

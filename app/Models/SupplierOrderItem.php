<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_order_id',
        'product_id',
        'quantity',
        'supplier_price',
        'total_price',
    ];

    /* ===== Relations ===== */

    public function order()
    {
        return $this->belongsTo(SupplierOrder::class, 'supplier_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SupplierProduct extends Pivot
{
    protected $table = 'supplier_product';

    // الحقول القابلة للتعبئة
    protected $fillable = [
        'supplier_id',
        'product_id',
        'supplier_price',
        'min_quantity',
        'active',
    ];

    /**
     * علاقة المورد
     */
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    /**
     * علاقة المنتج
     */
    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }

    /**
     * توضيح أن هذا Pivot يحتوي على timestamps
     */
    public $timestamps = true;
}

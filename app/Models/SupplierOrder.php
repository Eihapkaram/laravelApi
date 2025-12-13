<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'created_by',
        'total_price',
        'status',
        'notes',
        'responded_at',
        'supplier_reject_reason',
    ];

    /* ===== Relations ===== */

    // المورد
    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    // الأدمن اللي أنشأ الطلب
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // عناصر الطلب
    public function items()
    {
        return $this->hasMany(SupplierOrderItem::class);
    }
}

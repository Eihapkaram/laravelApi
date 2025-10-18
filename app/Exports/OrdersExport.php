<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OrdersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Order::select(
            'id',
            'user_id',
            'seller_id',
            'store_name',
            'status',
            'total_price',
            'shipping_address',
            'payment_method',
            'store_banner',
            'approval_status',
            'approved_at',
            'city',
            'governorate',
            'street',
            'phone',
            'created_at',
            'updated_at'
        )->get();
    }

    public function headings(): array
    {
        return [
            'id',
            'user_id',
            'seller_id',
            'store_name',
            'status',
            'total_price',
            'shipping_address',
            'payment_method',
            'store_banner',
            'approval_status',
            'approved_at',
            'city',
            'governorate',
            'street',
            'phone',
            'created_at',
            'updated_at',
        ];
    }
}

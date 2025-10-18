<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Order::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'User ID',
            'Seller ID',
            'Store Name',
            'Status',
            'Total Price',
            'Shipping Address',
            'Payment Method',
            'Store Banner',
            'Approval Status',
            'Approved At',
            'City',
            'Governorate',
            'Street',
            'Phone',
            'Created At',
            'Updated At',
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->user_id,
            $order->seller_id,
            $order->store_name,
            $order->status,
            $order->total_price,
            $order->shipping_address,
            $order->payment_method,
            $order->store_banner,
            $order->approval_status,
            $order->approved_at ? $order->approved_at->format('Y-m-d H:i:s') : '',
            $order->city,
            $order->governorate,
            $order->street,
            "'".$order->phone, // نجمة صغيرة لجعل الرقم كنص
            $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
            $order->updated_at ? $order->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }
}

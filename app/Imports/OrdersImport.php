<?php

namespace App\Imports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OrdersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Order([
            'user_id'         => $row['user_id'],
            'seller_id'       => $row['seller_id'],
            'store_name'      => $row['store_name'] ?? null,
            'status'          => $row['status'] ?? 'pending',
            'total_price'     => $row['total_price'] ?? 0,
            'shipping_address'=> $row['shipping_address'] ?? null,
            'payment_method'  => $row['payment_method'] ?? 'cod',
            'store_banner'    => $row['store_banner'] ?? null,
            'approval_status' => $row['approval_status'] ?? 'pending',
            'approved_at'     => $row['approved_at'] ?? null,
            'city'            => $row['city'] ?? null,
            'governorate'     => $row['governorate'] ?? null,
            'street'          => $row['street'] ?? null,
            'phone'           => $row['phone'] ?? null,
        ]);
    }
}

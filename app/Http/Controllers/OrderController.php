<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function createOrder()
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthorized. Please login first.'], 401);
    }

    $cart = $user->getcart()->with('proCItem.product')->first();//ترجع cart ب عناصرها


if (!$cart || $cart->proCItem->isEmpty()) {
    return response()->json([
        'message' => 'Cart is empty'
    ], 400);
}
    $total = $cart->proCItem->sum(function($item) {
        return $item->quantity * $item->product->price;
    });

    $order = Order::create([
        'user_id' => $user->id,
        'total_price'   => $total,
        'status'  => 'pending',
    ]);
    foreach ($cart->proCItem as $item) {
        $order->orderdetels()->create([
            'product_id' => $item->product_id,
            'quantity'   => $item->quantity,
            'price'      => $item->product->price,
        ]);
    }

    // بعد إنشاء الطلب،addorder ممكن تمسح السلة يحذف cart-items
    $cart->proCItem()->truncate();

    return response()->json([
        'message' => 'Order created successfully',
        'order'   => $order->load('orderdetels.product')
    ], 201);
}

public function showOrder() {
    $user=auth()->user();
     $order = $user->getOrder()->with('orderdetels.product')->get();
        return response()->json([
        'message' => 'youer Order show  successfully',
        'order'   => $order,
    ], 201);


}
public function showlatestOrder() {
    $user=auth()->user();
     $order = $user->getOrder()->with('orderdetels.product')->get();
     $orderlatest = $user->getOrder()->with('orderdetels.product')->latest()->first();
        return response()->json([
        'message' => 'youer Order show  successfully',
        'order'   => $order,
        'orderlatest' => $orderlatest
    ], 201);


}
public function deleteOrder(Request $request) {
    $user=auth()->user();
    $order=$user->getOrder->first();
    $order->destroy($request->id);
        return response()->json([
        'message' => 'done delete Order successfully',
    ], 201);


}
public function deleteAllOrder() {
    $user=auth()->user();
    $order=$user->getOrder();
    $order->truncate();
        return response()->json([
        'message' => 'done delete All Order successfully',
    ], 201);


}

}

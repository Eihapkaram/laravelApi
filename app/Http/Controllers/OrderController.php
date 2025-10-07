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

        $cart = $user->getcart()->with('proCItem.product')->first(); // ترجع cart ب عناصرها

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
            'total_price' => $total,
            'status' => 'pending',
        ]);

        foreach ($cart->proCItem as $item) {
            $order->orderdetels()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
            ]);
        }

        // بعد إنشاء الطلب، يمكن مسح cart-items
        $cart->proCItem()->truncate();

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load('orderdetels.product')
        ], 201);
    }

    public function showOrder()
    {
        $user = auth()->user();
        $order = $user->getOrder()->with('orderdetels.product')->get();

        return response()->json([
            'message' => 'Your orders fetched successfully',
            'order' => $order,
        ], 201);
    }

    public function showLatestOrder()
    {
        $user = auth()->user();
        $order = $user->getOrder()->with('orderdetels.product')->get();
        $orderLatest = $user->getOrder()->with('orderdetels.product')->latest()->first();

        return response()->json([
            'message' => 'Your orders fetched successfully',
            'order' => $order,
            'orderLatest' => $orderLatest,
        ], 201);
    }

    public function deleteOrder(Request $request)
    {
        $user = auth()->user();
        $order = $user->getOrder()->first();
        $order->destroy($request->id);

        return response()->json([
            'message' => 'Order deleted successfully',
        ], 201);
    }

    public function deleteAllOrder()
    {
        $user = auth()->user();
        $order = $user->getOrder();
        $order->truncate();

        return response()->json([
            'message' => 'All orders deleted successfully',
        ], 201);
    }

    // 🔹 دالة لجلب كل الطلبات لجميع المستخدمين
    public function getAllOrders()
    {
        $orders = Order::with('orderdetels.product', 'userorder')->get();

        return response()->json([
            'message' => 'All orders fetched successfully',
            'orders' => $orders,
        ], 200);
    }

    // 🔹 دالة لجلب أحدث طلب تم إنشاؤه
    public function getLatestOrder()
    {
        $latestOrder = Order::with('orderdetels.product', 'userorder')->latest()->first();

        return response()->json([
            'message' => 'Latest order fetched successfully',
            'latestOrder' => $latestOrder,
        ], 200);
    }
}

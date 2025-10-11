<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    // إنشاء طلب جديد
    public function createOrder(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized. Please login first.'], 401);
        }

        $cart = $user->getcart()->with('proCItem.product')->first(); // ترجع cart بعناصرها

        if (!$cart || $cart->proCItem->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        $total = $cart->proCItem->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        $order = Order::create([
            'user_id'      => $user->id,
            'total_price'  => $total,
            'status'       => $request->status ?? 'pending',
            'city'         => $request->city,
            'governorate'  => $request->governorate,
            'street'       => $request->street,
            'phone'        => $request->phone,
            'store_name'   => $request->store_name,
        ]);

        foreach ($cart->proCItem as $item) {
            $order->orderdetels()->create([
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'price'      => $item->product->price,
            ]);
        }

        // بعد إنشاء الطلب نحذف عناصر السلة
        $cart->proCItem()->truncate();

        return response()->json([
            'message' => 'Order created successfully',
            'order'   => $order->load('orderdetels.product')
        ], 201);
    }

    // عرض طلبات المستخدم الحالي
    public function showOrder()
    {
        $user = auth()->user();
        $order = $user->getOrder()->with('orderdetels.product')->get();

        return response()->json([
            'message' => 'Your orders fetched successfully',
            'order'   => $order,
        ], 200);
    }

    // عرض آخر طلب تم إنشاؤه للمستخدم الحالي
    public function showlatestOrder()
    {
        $user = auth()->user();
        $order = $user->getOrder()->with('orderdetels.product')->get();
        $orderlatest = $user->getOrder()->with('orderdetels.product')->latest()->first();

        return response()->json([
            'message' => 'Latest order fetched successfully',
            'order'   => $order,
            'orderlatest' => $orderlatest,
        ], 200);
    }

    // 🔹 عرض جميع الطلبات لكل المستخدمين (للمشرفين)
    public function showAllOrders()
    {
        $orders = Order::with(['orderdetels.product', 'userorder'])->latest()->get();

        return response()->json([
            'message' => 'All orders fetched successfully',
            'orders'  => $orders,
        ], 200);
    }

    // حذف طلب معين
    public function deleteOrder(Request $request)
    {
        $user = auth()->user();
        $order = $user->getOrder()->find($request->id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully',
        ], 200);
    }

    // حذف كل الطلبات الخاصة بالمستخدم الحالي
    public function deleteAllOrder()
    {
        $user = auth()->user();
        $user->getOrder()->delete();

        return response()->json([
            'message' => 'All orders deleted successfully',
        ], 200);
    }
}

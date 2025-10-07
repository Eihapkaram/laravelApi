<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function createOrder(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized. Please login first.'], 401);
        }

        // ✅ التحقق من صحة البيانات المدخلة
        $request->validate([
            'store_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'governorate' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'phone' => [
                'required',
                'regex:/^(011|012|015)[0-9]{8}$/'
            ],
        ], [
            'store_name.required' => 'اسم المحل مطلوب',
            'city.required' => 'المدينة مطلوبة',
            'governorate.required' => 'المحافظة مطلوبة',
            'street.required' => 'الشارع مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex' => 'رقم الهاتف يجب أن يتكون من 11 رقم ويبدأ بـ 011 أو 012 أو 015',
        ]);

        // ✅ جلب السلة الخاصة بالمستخدم مع المنتجات
        $cart = $user->getcart()->with('proCItem.product')->first();

        if (!$cart || $cart->proCItem->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ], 400);
        }

        // ✅ حساب الإجمالي
        $total = $cart->proCItem->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        // ✅ إنشاء الطلب
        $order = Order::create([
            'user_id' => $user->id,
            'total_price' => $total,
            'status' => 'pending',
            'city' => $request->city,
            'governorate' => $request->governorate,
            'street' => $request->street,
            'phone' => $request->phone,
            'store_name' => $request->store_name,
        ]);

        // ✅ إنشاء تفاصيل الطلب
        foreach ($cart->proCItem as $item) {
            $order->orderdetels()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price,
            ]);
        }

        // ✅ حذف محتوى السلة بعد الطلب
        $cart->proCItem()->delete();

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load('orderdetels.product')
        ], 201);
    }

    // ✅ عرض كل الطلبات
    public function showOrder()
    {
        $user = auth()->user();
        $orders = $user->getOrder()->with('orderdetels.product')->get();

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'orders' => $orders,
        ], 200);
    }

    // ✅ عرض أحدث طلب
    public function showlatestOrder()
    {
        $user = auth()->user();
        $orderlatest = $user->getOrder()->with('orderdetels.product')->latest()->first();

        return response()->json([
            'message' => 'Latest order retrieved successfully',
            'orderlatest' => $orderlatest,
        ], 200);
    }

    // ✅ حذف طلب معين
    public function deleteOrder(Request $request)
    {
        $user = auth()->user();

        $order = $user->getOrder()->where('id', $request->id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully',
        ], 200);
    }

    // ✅ حذف جميع الطلبات
    public function deleteAllOrder()
    {
        $user = auth()->user();
        $user->getOrder()->delete();

        return response()->json([
            'message' => 'All orders deleted successfully',
        ], 200);
    }
}

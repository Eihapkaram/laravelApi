<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

        // ✅ التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'city'         => 'required|string|max:100',
            'governorate'  => 'required|string|max:100',
            'street'       => 'required|string|max:255',
            'phone'        => ['required', 'regex:/^(010|011|012|015)[0-9]{8}$/'],
            'store_name'   => 'nullable|string|max:255',
            'status'       => 'nullable|string|in:pending,paid,shipped,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $cart = $user->getcart()->with('proCItem.product')->first();

        if (!$cart || $cart->proCItem->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // حساب الإجمالي
        $total = $cart->proCItem->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        // إنشاء الطلب
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

    // عرض آخر طلب تم إنشاؤه
    public function showlatestOrder()
    {
        $user = auth()->user();
        $orderlatest = $user->getOrder()->with('orderdetels.product')->latest()->first();

        return response()->json([
            'message' => 'Latest order fetched successfully',
            'orderlatest' => $orderlatest,
        ], 200);
    }

    // عرض جميع الطلبات (للمشرفين)
    public function showAllOrders()
    {
        $orders = Order::with(['orderdetels.product', 'userorder'])->latest()->get();

        return response()->json([
            'message' => 'All orders fetched successfully',
            'orders'  => $orders,
        ], 200);
    }

    // ✅ تعديل حالة الطلب
    public function updateOrderStatus(Request $request, $id)
    {
        $user = auth()->user();
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->user_id !== $user->id && !$user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,paid,shipped,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order'   => $order,
        ], 200);
    }

    // ✅ حذف طلب معين (مسموح للمستخدم أو admin)
    public function deleteOrder(Request $request)
    {
        $user = auth()->user();
        $order = Order::find($request->id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // يسمح فقط لصاحب الطلب أو للمشرف بالحذف
        if ($order->user_id !== $user->id && !$user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully',
        ], 200);
    }

    // ✅ حذف كل الطلبات الخاصة بالمستخدم الحالي (أو كل الطلبات إن كان admin)
    public function deleteAllOrder()
    {
        $user = auth()->user();

        if ($user->is_admin) {
            Order::truncate(); // حذف كل الطلبات
            return response()->json(['message' => 'All orders deleted by admin'], 200);
        }

        $user->getOrder()->delete();

        return response()->json([
            'message' => 'All your orders deleted successfully',
        ], 200);
    }
}

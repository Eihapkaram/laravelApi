<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerCustomerController extends Controller
{
    // عرض العملاء التابعين للبائع
    public function index()
    {
        $seller = Auth::user();
        $customers = $seller->customers; // العملاء التابعين له
        return response()->json($customers);
    }

    // مسجل ف الموقع  إضافة عميل للبائع
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:users,id',
        ]);

        $seller = Auth::user();

        // تأكد أن المستخدم فعلاً بائع
        if ($seller->role !== 'seller') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $seller->customers()->syncWithoutDetaching([$request->customer_id]);

        return response()->json(['message' => 'تم إضافة العميل بنجاح']);
    }

    // حذف عميل
    public function destroy($customerId)
    {
        $seller = Auth::user();

        $seller->customers()->detach($customerId);

        return response()->json(['message' => 'تم حذف العميل']);
    }
    public function createNewCustomer(Request $request)
    {
        $seller = Auth::user();

        // تأكد أن المستخدم الحالي هو بائع
        if ($seller->role !== 'seller') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        // التحقق من البيانات المطلوبة
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        // هل يوجد عميل بنفس رقم الهاتف؟
        $customer = User::where('phone', $validated['phone'])->first();

        if (!$customer) {
            // لو مش موجود → إنشاء عميل جديد
            $customer = User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'role' => 'customer',
                'password' => bcrypt('12345678'), // مؤقتًا
            ]);
        }

        // ربط العميل بالبائع (بدون تكرار)
        $seller->customers()->syncWithoutDetaching([$customer->id]);

        return response()->json([
            'message' => $customer->wasRecentlyCreated
                ? 'تم إنشاء العميل وربطه بنجاح'
                : 'تم ربط العميل الموجود مسبقًا بالبائع بنجاح',
            'customer' => $customer
        ]);
    }
    public function myCustomers(Request $request)
    {
        $seller = Auth::user();

        if ($seller->role !== 'seller') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $query = $seller->customers()
            ->select('id', 'name', 'phone', 'created_at')
            ->orderBy('created_at', 'desc');

        // 🔍 البحث بالاسم أو رقم الهاتف
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        // 📄 الترقيم (10 عملاء في الصفحة)
        $customers = $query->paginate(10);

        return response()->json($customers);
    }
    public function show($id)
    {
        $seller = Auth::user();

        // تأكد أن العميل تابع فعلاً للبائع
        $customer = $seller->customers()->where('users.id', $id)->first();

        if (!$customer) {
            return response()->json(['message' => 'العميل غير موجود أو غير تابع لك'], 404);
        }

        // يمكن تضيف معلومات إضافية لاحقًا (زي عدد الطلبات)
        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'created_at' => $customer->created_at,
            // 'orders_count' => $customer->orders()->count(),  ← لو عندك جدول طلبات
        ]);
    }
    public function customerOrders($customerId)
{
    $seller = Auth::user();

    // تأكد أن العميل فعلاً تابع للبائع
    $isLinked = $seller->customers()->where('users.id', $customerId)->exists();

    if (!$isLinked) {
        return response()->json(['message' => 'العميل غير تابع لك'], 403);
    }

    // جلب الطلبات اللي أنشأها هذا البائع لهذا العميل
    $orders = Order::with('orderdetels.product')
        ->where('seller_id', $seller->id)
        ->where('user_id', $customerId)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($orders);
}
// Route: GET /seller/customers/{customer}/orders
public function sellerCustomerOrders($customer_id)
{
    $seller = auth()->user();

    if ($seller->role !== 'seller') {
        return response()->json(['error' => 'غير مصرح لك'], 403);
    }

    $orders = Order::with(['orderdetels.product'])
        ->where('seller_id', $seller->id)
        ->where('user_id', $customer_id)
        ->whereHas('userorder', function($q) {
            $q->where('role', 'customer');
        })
        ->get();

    return response()->json($orders);
}

}

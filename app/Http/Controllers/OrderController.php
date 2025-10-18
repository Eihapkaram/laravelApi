<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Notifications\CreatOrder;
use App\Notifications\NewOrderNotification;
use App\Notifications\UpdateOrder;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notifiable;
use App\Notifications\OrderCreatedBySellerNotification;
use App\Notifications\OrderApprovedNotification;
use App\Notifications\OrderRejectedNotification;
use App\Imports\OrdersImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;

class OrderController extends Controller
{
    // إنشاء طلب جديد
    public function createOrder(Request $request)
    {
        $user = auth()->user();


        if (!$user) {
            return response()->json(['message' => 'غير مصرح. برجاء تسجيل الدخول أولاً.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'city'         => 'required|string|max:100',
            'governorate'  => 'required|string|max:100',
            'street'       => 'required|string|max:255',
            'phone'        => ['required', 'regex:/^(010|011|012|015)[0-9]{8}$/'],
            'store_name'   => 'nullable|string|max:255',
            'status'       => 'nullable|string|in:pending,paid,shipped,completed,cancelled',
            'payment_method' => 'nullable|string|in:cod,credit_card,paypal',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors'  => $validator->errors()
            ], 422);
        }

        $cart = $user->getcart()->with('proCItem.product')->first();

        if (!$cart || $cart->proCItem->isEmpty()) {
            return response()->json(['message' => 'السلة فارغة'], 400);
        }

        $total = $cart->proCItem->sum(fn($item) => $item->quantity * $item->product->price);
        
        $order = Order::create([
            'user_id'        => $user->id,
            'total_price'    => $total,
            'status'         => $request->status ?? 'pending',
            'city'           => $request->city,
            'governorate'    => $request->governorate,
            'street'         => $request->street,
            'phone'          => $request->phone,
            'store_name'     => $request->store_name,
            'payment_method' => $request->payment_method,
            
        ]);
        if ($order) {
            // جيب كل المستخدمين اللي رولهم أدمن
            $admins = User::where('role', 'admin')->get();

            // ابعت الإشعار ليهم
            Notification::send($admins, new CreatOrder($user, $order));
            // 🔔 إرسال إشعار للمستخدم نفسه
            $user->notify(new NewOrderNotification($order));
        }

        foreach ($cart->proCItem as $item) {
            $order->orderdetels()->create([
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'price'      => $item->product->price,
            ]);
        }

        $cart->proCItem()->truncate();

        return response()->json([
            'message' => 'تم إنشاء الطلب بنجاح',
            'order'   => $order->load('orderdetels.product')
        ], 201);
    }



    // ✅ إنشاء طلب بواسطة بائع
    public function createBySeller(Request $request)
    {
        $seller = auth()->user();

        if ($seller->role !== 'seller') {
            return response()->json(['error' => 'غير مصرح لك بإنشاء طلبات'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'total_price' => 'required|numeric',
            'shipping_address' => 'required|string',
            'city' => 'nullable|string',
            'governorate' => 'nullable|string',
            'street' => 'nullable|string',
            'phone' => 'nullable|string',
            'store_banner' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        $customer = User::find($request->user_id);

        if ($customer->role !== 'customer') {
            return response()->json(['error' => 'يمكنك فقط إنشاء طلبات لعملاء'], 400);
        }
        // رفع الصورة الرئيسية
        $path = null;
        if ($request->hasFile('banner')) {
            $image = $request->file('banner')->getClientOriginalName();
            $path = $request->file('banner')->storeAs('storebanners', $image, 'public');
        }

        $order = Order::create([
            'user_id' => $customer->id,
            'seller_id' => $seller->id,
            'total_price' => $request->total_price,
            'shipping_address' => $request->shipping_address,
            'city' => $request->city,
            'governorate' => $request->governorate,
            'street' => $request->street,
            'phone' => $request->phone,
            'status' => 'pending',
            'approval_status' => 'pending',
            'store_banner' => $path,
        ]);
        $customer->notify(new OrderCreatedBySellerNotification($order, $seller));
        return response()->json([
            'message' => 'تم إنشاء الطلب بنجاح في انتظار موافقة العميل',
            'order' => $order->load('orderdetels.product','userorder'),
        ], 201);
    }


    // ✅ موافقة العميل على الطلب
    public function approveOrder($id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if ($order->user_id !== $user->id) {
            return response()->json(['error' => 'ليس لديك صلاحية للموافقة على هذا الطلب'], 403);
        }

        $order->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
        $order->seller->notify(new OrderApprovedNotification($order, $user));
        return response()->json(['message' => 'تمت الموافقة على الطلب', 'order' => $order->load('orderdetels.product', 'userorder'),]);
    }
    // ✅ رفض الطلب
    public function rejectOrder($id)
    {
        $user = auth()->user();
        $order = Order::findOrFail($id);

        if ($order->user_id !== $user->id) {
            return response()->json(['error' => 'ليس لديك صلاحية لرفض هذا الطلب'], 403);
        }

        $order->update(['approval_status' => 'rejected']);
        $order->seller->notify(new OrderRejectedNotification($order, $user));


        return response()->json(['message' => 'تم رفض الطلب', 'order' => $order->load('orderdetels.product', 'userorder'),]);
    }

    // عرض  عدد طلبات المستخدم الحالي
    public function OrderCount()
    {
        $user = auth()->user();
        $order = $user->getOrder()->count();

        return response()->json([
            'message' => 'تم جلب  عدد الطلبات الخاصة بك بنجاح',
            'orderCount'   => $order,
        ], 200);
    }
    // عرض طلبات المستخدم الحالي
    public function showOrder()
    {
        $user = auth()->user();
        $order = $user->getOrder()->with('orderdetels.product','seller')->get();

        return response()->json([
            'message' => 'تم جلب الطلبات الخاصة بك بنجاح',
            'order'   => $order,
        ], 200);
    }

    // عرض آخر طلب
    public function showlatestOrder()
    {
        $user = auth()->user();
        $orderlatest = $user->getOrder()->with('orderdetels.product','seller')->latest()->first();

        return response()->json([
            'message' => 'تم جلب آخر طلب بنجاح',
            'orderlatest' => $orderlatest,
        ], 200);
    }

    // عرض جميع الطلبات (لـ admin فقط)
    public function showAllOrders()
    {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح - فقط للمديرين'], 403);
        }

        $orders = Order::with(['orderdetels.product', 'userorder','seller'])->latest()->get();

        return response()->json([
            'message' => 'تم جلب جميع الطلبات بنجاح',
            'orders'  => $orders,
        ], 200);
    }

    // تعديل حالة الطلب (مسموح للمستخدم العادي)
    public function updateOrderStatus(Request $request, $id)
    {
        $user = auth()->user();
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'الطلب غير موجود'], 404);
        }

        // السماح فقط لصاحب الطلب أو الأدمن
        if ($order->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بتعديل هذا الطلب'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,paid,shipped,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطأ في التحقق من الحالة الجديدة',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // لو المستخدم العادي بيحاول يعدل
        if ($user->role !== 'admin') {
            // الحالات المسموح بها فقط للمستخدم
            if (!in_array($request->status, ['pending', 'cancelled'])) {
                return response()->json([
                    'message' => 'يمكنك فقط إلغاء الطلب أو إرجاعه لحالة الانتظار'
                ], 403);
            }

            // لا يمكن تعديل حالة الطلب بعد الشحن أو الإكمال
            if (in_array($order->status, ['shipped', 'completed'])) {
                return response()->json([
                    'message' => 'لا يمكنك تعديل الطلب بعد شحنه أو إكماله'
                ], 403);
            }
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'order'   => $order,
        ], 200);
        if ($order) {
            // جيب كل المستخدمين اللي رولهم أدمن
            $admins = User::where('role', 'admin')->get();

            // ابعت الإشعار ليهم
            Notification::send($admins, new UpdateOrder($user));
        }
    }

    // حذف طلب (للمستخدم أو admin)
    public function deleteOrder($id)
    {
        $user = auth()->user();
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'الطلب غير موجود'], 404);
        }

        if ($order->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا الطلب'], 403);
        }

        $order->delete();

        return response()->json(['message' => 'تم حذف الطلب بنجاح'], 200);
    }

    // حذف كل الطلبات (للمستخدم أو admin)
    public function deleteAllOrder()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            Order::truncate();
            return response()->json(['message' => 'تم حذف جميع الطلبات بواسطة المدير'], 200);
        }

        $user->getOrder()->delete();

        return response()->json(['message' => 'تم حذف جميع طلباتك بنجاح'], 200);
    }
    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv'
    ]);

    Excel::import(new OrdersImport, $request->file('file'));

    return response()->json(['message' => '✅ تم استيراد الطلبات بنجاح']);
}
public function export()
{
    $fileName = 'orders-' . now()->format('Y-m-d_H-i-s') . '.xlsx';
    return Excel::download(new OrdersExport, $fileName);
}
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\WithdrawRequest;
use App\Notifications\WithdrawRequestApproved;
use App\Notifications\WithdrawRequestRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Notifications\WithdrawRequestSubmitted;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewWithdrawRequest;
use Illuminate\Support\Str;
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
    // ✅ إضافة عميل للبائع بالهاتف أو الإيميل
    public function store(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string', // ممكن يكون phone أو email
        ]);

        $seller = Auth::user();

        // تأكد أن المستخدم فعلاً بائع
        if ($seller->role !== 'seller') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        // البحث عن العميل حسب الإيميل أو رقم الهاتف
        $customer = User::where('email', $request->identifier)
            ->orWhere('phone', $request->identifier)
            ->first();

        if (!$customer) {
            return response()->json(['message' => 'العميل غير موجود في النظام'], 404);
        }

        // ربط البائع بالعميل بدون تكرار
        $seller->customers()->syncWithoutDetaching([$customer->id]);

        return response()->json(['message' => 'تم إضافة العميل بنجاح']);
    }

    // حذف عميل
    public function destroy($customerId)
    {
        $seller = Auth::user();

        $seller->customers()->detach($customerId);

        return response()->json(['message' => 'تم حذف العميل']);
    }
    // إنشاء عميل جديد وربطه بالبائع + توليد رابط واتساب
   public function createNewCustomer(Request $request)
{
    $seller = Auth::user();

    if ($seller->role !== 'seller') {
        return response()->json(['message' => 'غير مصرح لك'], 403);
    }

    // ✅ التحقق من صحة البيانات
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    // 🔍 البحث عن العميل برقم الهاتف
    $customer = User::where('phone', $validated['phone'])->first();

    if (!$customer) {
        // ✅ إنشاء العميل الجديد
        $customer = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'role' => 'customer',
            'password' => bcrypt(Str::random(10)), // كلمة مرور مؤقتة
        ]);

        // ✅ إنشاء رمز (token) لتفعيل الحساب
        $token = Str::random(64);
        DB::table('password_resets')->insert([
            'phone' => $customer->phone,
            'token' => $token,
            'created_at' => now(),
        ]);

        // ✅ رابط الواجهة الأمامية (Vue)
        $frontendUrl = env('FRONTEND_URL', 'https://your-frontend-domain.com');
        $activationLink = "{$frontendUrl}/resetpassword?token={$token}&phone={$customer->phone}";

        // ✅ توليد رسالة ورابط واتساب
        $message = "مرحباً {$customer->name}!\nتم إنشاء حسابك. لتعيين كلمة المرور اضغط هنا:\n{$activationLink}";
        $phoneForWa = preg_replace('/[^0-9]/', '', $customer->phone);
        $waLink = "https://wa.me/{$phoneForWa}?text=" . urlencode($message);
    }

    // ✅ ربط العميل بالبائع (بدون تكرار)
    $seller->customers()->syncWithoutDetaching([$customer->id]);

    return response()->json([
        'message' => $customer->wasRecentlyCreated
            ? 'تم إنشاء العميل وربطه بنجاح، استخدم رابط واتساب لإرسال التفعيل.'
            : 'تم ربط العميل الموجود مسبقًا بالبائع بنجاح.',
        'customer' => $customer,
        'waLink' => $waLink ?? null,
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
            ->whereHas('userorder', function ($q) {
                $q->where('role', 'customer');
            })
            ->get();

        return response()->json($orders);
    }
    //عرض الارباح لكل مندوب
    // ✅ عرض أرباح المندوب الحالية بعد عمليات السحب
    public function myProfits()
    {
        $seller = Auth::user();

        // تأكد أن المستخدم فعلاً مندوب
        if ($seller->role !== 'seller') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        // إجمالي الطلبات الموافق عليها
        $orders = Order::where('seller_id', $seller->id)
            ->where('approval_status', 'approved')
            ->select('id', 'user_id', 'seller_profit', 'total_price', 'created_at')
            ->with('userorder:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        // حساب إجمالي الأرباح من الطلبات
        $totalProfit = $orders->sum('seller_profit');

        // حساب المبالغ المسحوبة (الموافق عليها فقط)
        $withdrawn = WithdrawRequest::where('seller_id', $seller->id)
            ->where('status', 'approved')
            ->sum('amount');

        // الرصيد المتاح بعد عمليات السحب
        $availableProfit = $totalProfit - $withdrawn;

        // تحديث الرصيد في جدول المستخدم
        $seller->available_profit = $availableProfit;
        $seller->save();

        return response()->json([
            'seller_id' => $seller->id,
            'seller_name' => $seller->name,
            'total_orders' => $orders->count(),
            'total_profit' => round($totalProfit, 2),
            'withdrawn' => round($withdrawn, 2),
            'available_profit' => round($availableProfit, 2),
            'orders' => $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'customer_name' => $order->userorder->name ?? '-',
                    'total_price' => $order->total_price,
                    'seller_profit' => $order->seller_profit,
                    'date' => $order->created_at->format('Y-m-d H:i'),
                ];
            }),
        ]);
    }

    // ✅ طلب سحب جزء من الأرباح
    public function requestWithdrawpart(Request $request)
    {
        $seller = auth()->user();

        if ($seller->role !== 'seller') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        // ✅ تحقق إن مفيش طلب سحب معلق بالفعل
        $pendingRequest = WithdrawRequest::where('seller_id', $seller->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'message' => 'لديك طلب سحب قيد المراجعة بالفعل، يرجى انتظار الموافقة عليه قبل إنشاء طلب جديد',
                'pending_request_id' => $pendingRequest->id,
            ], 400);
        }

        // إجمالي الأرباح من الطلبات الموافق عليها
        $totalProfit = Order::where('seller_id', $seller->id)
            ->where('approval_status', 'approved')
            ->sum('seller_profit');

        // إجمالي المبالغ التي تم سحبها فعلاً (تمت الموافقة عليها)
        $withdrawn = WithdrawRequest::where('seller_id', $seller->id)
            ->where('status', 'approved')
            ->sum('amount');

        // الأرباح المتاحة حالياً
        $available = $totalProfit - $withdrawn;

        if ($available <= 0) {
            return response()->json(['message' => 'لا توجد أرباح متاحة للسحب حالياً'], 400);
        }

        if ($request->amount > $available) {
            return response()->json([
                'message' => 'المبلغ المطلوب يتجاوز الأرباح المتاحة',
                'available' => round($available, 2)
            ], 400);
        }

        // ✅ إنشاء طلب سحب جديد
        $withdraw = WithdrawRequest::create([
            'seller_id' => $seller->id,
            'amount' => $request->amount,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        // ✅ إرسال إشعار للأدمن والمندوب
        $admins = User::whereIn('role', ['admin', 'delegate'])->get();
        Notification::send($admins, new NewWithdrawRequest($seller, $request->amount));

        // ✅ إشعار للبائع نفسه
        $seller->notify(new WithdrawRequestSubmitted($request->amount));

        return response()->json([
            'message' => 'تم إرسال طلب السحب بنجاح وبانتظار المراجعة',
            'withdraw_request' => $withdraw,
            'available_after_request' => round($available - $request->amount, 2)
        ], 200);
    }


    // طلب سحب ارباح المندوب 
    public function requestWithdraw(Request $request)
    {
        $seller = auth()->user();

        if ($seller->role !== 'seller') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        // احسب إجمالي الأرباح الحالية
        $totalProfit = Order::where('seller_id', $seller->id)
            ->where('approval_status', 'approved')
            ->sum('seller_profit');

        // احسب المبالغ اللي تم سحبها سابقًا
        $withdrawn = WithdrawRequest::where('seller_id', $seller->id)
            ->where('status', 'approved')
            ->sum('amount');

        $available = $totalProfit - $withdrawn;

        if ($request->amount > $available) {
            return response()->json([
                'message' => 'المبلغ المطلوب يتجاوز الأرباح المتاحة',
                'available' => $available
            ], 400);
        }

        $withdraw = WithdrawRequest::create([
            'seller_id' => $seller->id,
            'amount' => $request->amount,
            'note' => $request->note,
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب السحب بنجاح، بانتظار المراجعة',
            'withdraw_request' => $withdraw,
        ], 201);
    }
    //عرض طلب سحب للمندوب 
    public function myWithdraws()
    {
        $seller = auth()->user();

        if ($seller->role !== 'seller') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $withdraws = WithdrawRequest::where('seller_id', $seller->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($withdraws);
    }

    //عرض ارباح كل مندوب 
    public function sellersProfits()
    {
        // تأكد أن المستخدم أدمن فقط
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        // اجمع أرباح كل بائع بناءً على الطلبات الموافق عليها
        $profits = Order::selectRaw('seller_id, COUNT(*) as total_orders, SUM(seller_profit) as total_profit')
            ->whereNotNull('seller_id')
            ->where('approval_status', 'approved')
            ->groupBy('seller_id')
            ->with('seller:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'seller_id' => $item->seller_id,
                    'seller_name' => $item->seller?->name ?? 'غير معروف',
                    'total_orders' => $item->total_orders,
                    'total_profit' => round($item->total_profit, 2),
                ];
            });

        return response()->json($profits);
    }
    // الموافقه او رفض لطلب سحب الارباح ادمن 
    // ✅ الموافقه او رفض لطلب سحب الارباح ادمن 
    public function approveWithdraw($id, Request $request)
    {
        $admin = auth()->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'note' => 'nullable|string|max:255',
        ]);

        $withdraw = WithdrawRequest::with('seller')->findOrFail($id);

        if ($withdraw->status !== 'pending') {
            return response()->json(['message' => 'تمت معالجة هذا الطلب مسبقًا'], 400);
        }

        // ✅ لو تمت الموافقة يتم خصم المبلغ من أرباح المندوب
        if ($request->status === 'approved') {
            $seller = $withdraw->seller;

            // احسب الأرباح الكلية للمندوب من الطلبات الموافق عليها
            $totalProfit = Order::where('seller_id', $seller->id)
                ->where('approval_status', 'approved')
                ->sum('seller_profit');

            // احسب المبالغ المسحوبة سابقًا
            $withdrawn = WithdrawRequest::where('seller_id', $seller->id)
                ->where('status', 'approved')
                ->sum('amount');

            // احسب الرصيد الحالي بعد العملية الجديدة
            $available = $totalProfit - $withdrawn;

            // حدث الرصيد في جدول المستخدمين
            $seller->available_profit = $available;
            $seller->save();
        }

        // تحديث حالة طلب السحب
        $withdraw->update([
            'status' => $request->status,
            'note' => $request->note,
        ]);
        // ✅ لو الحالة approved نرسل إشعار للمندوب
        if ($withdraw->status === 'approved') {
            $withdraw->seller->notify(new WithdrawRequestApproved($withdraw->amount));
        }

        // ❌ لو الحالة rejected نرسل إشعار رفض
        if ($withdraw->status === 'rejected') {
            $withdraw->seller->notify(new WithdrawRequestRejected($withdraw->amount, $withdraw->note));
        }
        return response()->json([
            'message' => 'تم تحديث حالة طلب السحب بنجاح',
            'withdraw' => $withdraw,
            'new_balance' => $withdraw->seller->available_profit ?? null,
        ]);
    }


    // ✅ عرض كل طلبات السحب (للأدمن)
    public function allWithdrawRequests()
    {
        $admin = auth()->user();

        // تأكد أن المستخدم أدمن
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        // جلب جميع طلبات السحب مع بيانات المندوب
        $withdraws = WithdrawRequest::with('seller:id,name,phone')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'seller_id' => $item->seller_id,
                    'seller_name' => $item->seller->name ?? '-',
                    'seller_phone' => $item->seller->phone ?? '-',
                    'amount' => $item->amount,
                    'status' => $item->status,
                    'note' => $item->note,
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'message' => 'تم جلب طلبات السحب بنجاح',
            'withdraw_requests' => $withdraws,
        ]);
    }
    // ✅ تحديث حالة طلب السحب (للأدمن)
    public function updateWithdrawStatus($id, Request $request)
    {
        $admin = auth()->user();

        // تأكد أن المستخدم أدمن
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح لك'], 403);
        }

        // التحقق من البيانات المدخلة
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'note' => 'nullable|string|max:255',
        ]);

        // جلب الطلب
        $withdraw = WithdrawRequest::with('seller:id,name')->findOrFail($id);

        // منع التحديث لو الطلب بالفعل تمت معالجته
        if ($withdraw->status !== 'pending') {
            return response()->json([
                'message' => 'لا يمكن تعديل حالة طلب تمت معالجته مسبقاً',
            ], 400);
        }

        // تحديث الحالة
        $withdraw->status = $request->status;
        $withdraw->note = $request->note;

        // لو تم الموافقة، احفظ تاريخ الموافقة
        if ($request->status === 'approved') {
            $withdraw->approved_at = now();
        }
        // ✅ لو الحالة approved نرسل إشعار للمندوب
        if ($withdraw->status === 'approved') {
            $withdraw->seller->notify(new WithdrawRequestApproved($withdraw->amount));
        }
        // ❌ لو الحالة rejected نرسل إشعار رفض
        if ($withdraw->status === 'rejected') {
            $withdraw->seller->notify(new WithdrawRequestRejected($withdraw->amount, $withdraw->note));
        }

        $withdraw->save();

        return response()->json([
            'message' => 'تم تحديث حالة طلب السحب بنجاح',
            'withdraw' => [
                'id' => $withdraw->id,
                'seller_name' => $withdraw->seller->name ?? '-',
                'amount' => $withdraw->amount,
                'status' => $withdraw->status,
                'note' => $withdraw->note,
                'approved_at' => $withdraw->approved_at,
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Mpdf\Mpdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Notifications\CreatOrder;
use App\Notifications\NewOrderNotification;
use App\Notifications\UpdateOrder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Notifiable;
use App\Notifications\OrderCreatedBySellerNotification;
use App\Notifications\OrderApprovedNotification;
use App\Notifications\OrderRejectedNotification;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

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


    public function createBySeller(Request $request)
    {
        $seller = auth()->user();

        if ($seller->role !== 'seller') {
            return response()->json(['error' => 'غير مصرح لك بإنشاء طلبات'], 403);
        }

        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'city'          => 'required|string|max:100',
            'governorate'   => 'required|string|max:100',
            'street'        => 'required|string|max:255',
            'phone'         => ['required', 'regex:/^(010|011|012|015)[0-9]{8}$/'],
            'store_name'    => 'nullable|string|max:255',
            'status'        => 'nullable|string|in:pending,paid,shipped,completed,cancelled',
            'payment_method' => 'nullable|string|in:cod,credit_card,paypal',
        ]);

        // جلب العميل
        $customer = User::find($request->user_id);

        if ($customer->role !== 'customer') {
            return response()->json(['error' => 'يمكنك فقط إنشاء طلبات لعملاء'], 400);
        }

        // ✅ اجلب سلة البائع نفسه
        $cart = $seller->getcart()->with('proCItem.product')->first();

        if (!$cart || $cart->proCItem->isEmpty()) {
            return response()->json(['message' => 'سلة البائع فارغة'], 400);
        }

        // حساب الإجمالي
        $total = $cart->proCItem->sum(fn($item) => $item->quantity * $item->product->price);

        // إنشاء الطلب للعميل
        $order = Order::create([
            'user_id'        => $customer->id,
            'seller_id'      => $seller->id,
            'total_price'    => $total,
            'status'         => $request->status ?? 'pending',
            'city'           => $request->city,
            'governorate'    => $request->governorate,
            'street'         => $request->street,
            'phone'          => $request->phone,
            'store_name'     => $request->store_name,
            'payment_method' => $request->payment_method,
        ]);

        // إرسال إشعارات
        if ($order) {
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new CreatOrder($seller, $order)); // إشعار للأدمن
        }

        // نسخ تفاصيل السلة إلى تفاصيل الطلب
        foreach ($cart->proCItem as $item) {
            $order->orderdetels()->create([
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'price'      => $item->product->price,
            ]);
        }
        $customer->notify(new OrderCreatedBySellerNotification($order, $seller));
        // مسح سلة البائع بعد إنشاء الطلب
        $cart->proCItem()->truncate();

        return response()->json([
            'message' => 'تم إنشاء الطلب بنجاح للعميل',
            'order'   => $order->load('orderdetels.product', 'userorder'),
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
    // عرض طلبات المستخدم الحالي sales
    public function showOrder()
    {
        $user = auth()->user();
        $order = $user->getOrder()->with('orderdetels.product', 'seller')->get();

        return response()->json([
            'message' => 'تم جلب الطلبات الخاصة بك بنجاح',
            'order'   => $order,
        ], 200);
    }
public function showAllOrdersWithoutSeller()
{
    // ✅ جلب كل الطلبات التي لا تحتوي على seller_id
    $orders = Order::with(['orderdetels.product', 'userorder'])
        ->whereNull('seller_id')
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'message' => 'تم جلب كل الطلبات التي لم يتم إنشاؤها بواسطة بائع',
        'orders'  => $orders,
    ], 200);
}
public function showApprovedOrdersBySellers()
{
    // ✅ جلب الطلبات التي أنشأها بائع وتمت الموافقة عليها فقط
    $orders = Order::with(['orderdetels.product', 'userorder', 'seller'])
        ->whereNotNull('seller_id')
        ->where('approval_status', 'approved')
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'message' => 'تم جلب الطلبات الموافق عليها التي قام بها البائعون بنجاح',
        'orders'  => $orders,
    ], 200);
}

  public function showAllOrdersBySellers()
{
    // ✅ جلب كل الطلبات التي لها seller_id (أي أنشأها بائع)
    $orders = Order::with(['orderdetels.product', 'userorder', 'seller'])
        ->whereNotNull('seller_id')
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'message' => 'تم جلب كل الطلبات التي قام بها البائعون بنجاح',
        'orders'  => $orders,
    ], 200);
}

    // 1️⃣ جميع الطلبات التي أنشأها البائع (لكل العملاء)
    public function sellerOrdersForCustomers()
    {
        $seller = auth()->user();

        if ($seller->role !== 'seller') {
            return response()->json(['error' => 'غير مصرح لك'], 403);
        }

        // جلب كل الطلبات للبائع للعملاء الذين role = customer
        $orders = Order::with(['orderdetels.product', 'userorder'])
            ->where('seller_id', $seller->id)
            ->whereHas('userorder', function ($query) {
                $query->where('role', 'customer');
            })
            ->get();

        return response()->json([
            'message' => 'تم جلب الطلبات التي أنشأها البائع للعملاء بنجاح',
            'orders'  => $orders
        ], 200);
    }

    // ==========================
    // 3️⃣ جميع الطلبات الموافق عليها التي أنشأها البائع لكل العملاء
    // ==========================
    public function sellerApprovedOrders()
    {
        $seller = auth()->user();

        if ($seller->role !== 'seller') {
            return response()->json(['error' => 'غير مصرح لك'], 403);
        }

        $orders = Order::with('orderdetels.product', 'userorder')
            ->where('seller_id', $seller->id)
            ->whereNotNull('approved_at')
            ->get();

        return response()->json([
            'message' => 'تم جلب جميع الطلبات الموافق عليها للبائع',
            'orders' => $orders
        ], 200);
    }
    // 2️⃣ عدد الطلبات التي أنشأها البائع
    public function sellerOrdersCount()
    {
        $seller = auth()->user();

        if ($seller->role !== 'seller') {
            return response()->json(['error' => 'غير مصرح لك'], 403);
        }

        // جلب عدد الطلبات للبائع لعملاء لديهم role = 'customer'
        $count = Order::where('seller_id', $seller->id)
            ->whereHas('userorder', function ($query) {
                $query->where('role', 'customer');
            })
            ->count();

        return response()->json([
            'message' => 'تم جلب عدد الطلبات التي أنشأها البائع للعملاء',
            'count' => $count
        ], 200);
    }


    // عرض آخر طلب
    public function showlatestOrder()
    {
        $user = auth()->user();
        $orderlatest = $user->getOrder()->with('orderdetels.product', 'seller')->latest()->first();

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

        $orders = Order::with(['orderdetels.product', 'userorder', 'seller'])->latest()->get();

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
    //فاتور
    public function generateInvoice($id)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $order = Order::with('orderdetels.product', 'userorder')->findOrFail($id);
        $settings = Setting::first();

        $logoPath = $settings && $settings->logo
            ? public_path('storage/' . $settings->logo)
            : null;

        $html = '
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <style>
            body { 
                font-family: "dejavusans", sans-serif; 
                text-align: right; 
                direction: rtl; 
                font-size: 14px;
                color: #333;
            }
            h3 { 
                margin-bottom: 5px; 
                color: #1e3a8a; 
            }
            table { 
                border-collapse: collapse; 
                width: 100%; 
                margin-top: 15px; 
                font-size: 13px;
            }
            th, td { 
                border: 1px solid #000; 
                padding: 10px; 
                text-align: center; 
            }
            th { 
                background-color: #f3f4f6; 
                color: #111827; 
            }
            tbody tr:nth-child(even) { 
                background-color: #f9fafb; 
            }
            p, td { 
                margin: 4px 0; 
            }
            .total { 
                font-weight: bold; 
                font-size: 15px; 
                color: #1e40af; 
            }
            .signature { 
                margin-top: 40px; 
                font-size: 16px; 
                font-weight: bold; 
                color: #1e3a8a;
            }
            .customer-info p {
                margin: 2px 0;
            }
        </style>
    </head>
    <body>
        <div align="center">
            ' . (file_exists($logoPath) ? '<img src="' . $logoPath . '" width="120">' : '') . '
            <h3>فاتورة الطلب</h3>
        </div>

        <p><strong>رقم الطلب:</strong> ' . $order->id . '</p>
        <p><strong>تاريخ الطلب:</strong> ' . $order->created_at->format('Y-m-d') . '</p>

        <div class="customer-info">
            <h4>معلومات العميل</h4>
            <p><strong>الاسم:</strong> ' . $order->userorder->name . '</p>
            <p><strong>الهاتف:</strong> ' . $order->phone . '</p>
            <p><strong>العنوان:</strong> ' . $order->street . ', ' . $order->city . ', ' . $order->governorate . '</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($order->orderdetels as $item) {
            $html .= '<tr>
            <td>' . $item->product->titel . '</td>
            <td>' . $item->quantity . '</td>
            <td>' . number_format(round($item->price), 0) . ' جنيه</td>
            <td>' . number_format(round($item->price * $item->quantity), 0) . ' جنيه</td>
        </tr>';
        }

        $html .= '
            </tbody>
        </table>

        <p class="total"><strong>المجموع الكلي:</strong> ' . number_format(round($order->total_price), 0) . ' جنيه</p>

        <div class="signature" align="left">
            <p>توقيع الشركة:</p>
            ' . ($settings && $settings->site_name ? '<strong>' . $settings->site_name . '</strong>' : '') . '
        </div>
    </body>
    </html>';

        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans'
        ]);

        $mpdf->WriteHTML($html);

        return response()->streamDownload(function () use ($mpdf) {
            echo $mpdf->Output('', 'S');
        }, 'invoice-' . $order->id . '.pdf');
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
    // ✅ استيراد الطلبات باستخدام PhpSpreadsheet
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // تخطي الصف الأول (العناوين)
        foreach (array_slice($rows, 1) as $row) {
            if (!empty($row[1])) { // تأكد من وجود user_id مثلاً
                Order::updateOrCreate(
                    ['id' => $row[0] ?? null],
                    [
                        'user_id'        => $row[1] ?? null,
                        'seller_id'      => $row[2] ?? null,
                        'total_price'    => $row[3] ?? 0,
                        'status'         => $row[4] ?? 'pending',
                        'city'           => $row[5] ?? null,
                        'governorate'    => $row[6] ?? null,
                        'street'         => $row[7] ?? null,
                        'phone'          => $row[8] ?? null,
                        'payment_method' => $row[9] ?? null,
                        'approval_status' => $row[10] ?? 'pending',
                    ]
                );
            }
        }

        return response()->json(['message' => '✅ تم استيراد الطلبات بنجاح باستخدام PhpSpreadsheet']);
    }

    // ✅ تصدير الطلبات باستخدام PhpSpreadsheet
    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // رؤوس الأعمدة
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'User ID');
        $sheet->setCellValue('C1', 'Seller ID');
        $sheet->setCellValue('D1', 'Total Price');
        $sheet->setCellValue('E1', 'Status');
        $sheet->setCellValue('F1', 'City');
        $sheet->setCellValue('G1', 'Governorate');
        $sheet->setCellValue('H1', 'Street');
        $sheet->setCellValue('I1', 'Phone');
        $sheet->setCellValue('J1', 'Payment Method');
        $sheet->setCellValue('K1', 'Approval Status');

        // جلب الطلبات
        $orders = Order::with(['userorder', 'seller'])->get();
        $row = 2;

        foreach ($orders as $order) {
            $sheet->setCellValue('A' . $row, $order->id);
            $sheet->setCellValue('B' . $row, $order->user_id);
            $sheet->setCellValue('C' . $row, $order->seller_id);
            $sheet->setCellValue('D' . $row, $order->total_price);
            $sheet->setCellValue('E' . $row, $order->status);
            $sheet->setCellValue('F' . $row, $order->city);
            $sheet->setCellValue('G' . $row, $order->governorate);
            $sheet->setCellValue('H' . $row, $order->street);
            $sheet->setCellValue('I' . $row, $order->phone);
            $sheet->setCellValue('J' . $row, $order->payment_method);
            $sheet->setCellValue('K' . $row, $order->approval_status);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'orders-' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
    ///999999
    class OrderController extends Controller
{
    // ✅ استيراد الطلبات الخاصة بالعملاء (بدون seller_id)
    public function importCustomerOrders(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach (array_slice($rows, 1) as $row) {
            if (!empty($row[1])) { // user_id موجود
                Order::updateOrCreate(
                    ['id' => $row[0] ?? null],
                    [
                        'user_id'        => $row[1],
                        'seller_id'      => null, // ✅ بدون seller_id
                        'total_price'    => $row[3] ?? 0,
                        'status'         => $row[4] ?? 'pending',
                        'city'           => $row[5] ?? null,
                        'governorate'    => $row[6] ?? null,
                        'street'         => $row[7] ?? null,
                        'phone'          => $row[8] ?? null,
                        'payment_method' => $row[9] ?? null,
                        'approval_status' => $row[10] ?? 'pending',
                    ]
                );
            }
        }

        return response()->json(['message' => '✅ تم استيراد طلبات العملاء بنجاح']);
    }

    // ✅ تصدير الطلبات الخاصة بالعملاء (بدون seller_id)
    public function exportCustomerOrders()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // رؤوس الأعمدة
        $headers = ['ID', 'User ID', 'Seller ID', 'Total Price', 'Status', 'City', 'Governorate', 'Street', 'Phone', 'Payment Method', 'Approval Status'];
        $sheet->fromArray($headers, null, 'A1');

        // الطلبات التي لا تحتوي على seller_id
        $orders = Order::whereNull('seller_id')->orderBy('created_at', 'desc')->get();
        $row = 2;

        foreach ($orders as $order) {
            $sheet->fromArray([
                $order->id,
                $order->user_id,
                $order->seller_id,
                $order->total_price,
                $order->status,
                $order->city,
                $order->governorate,
                $order->street,
                $order->phone,
                $order->payment_method,
                $order->approval_status,
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'customer-orders-' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);
        (new Xlsx($spreadsheet))->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    // ✅ استيراد الطلبات الخاصة بالبائعين (approval_status = approved)
    public function importApprovedSellerOrders(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach (array_slice($rows, 1) as $row) {
            if (!empty($row[2])) { // seller_id موجود
                Order::updateOrCreate(
                    ['id' => $row[0] ?? null],
                    [
                        'user_id'        => $row[1] ?? null,
                        'seller_id'      => $row[2],
                        'total_price'    => $row[3] ?? 0,
                        'status'         => $row[4] ?? 'pending',
                        'city'           => $row[5] ?? null,
                        'governorate'    => $row[6] ?? null,
                        'street'         => $row[7] ?? null,
                        'phone'          => $row[8] ?? null,
                        'payment_method' => $row[9] ?? null,
                        'approval_status' => 'approved', // ✅ نثبّت الحالة
                    ]
                );
            }
        }

        return response()->json(['message' => '✅ تم استيراد طلبات البائعين الموافق عليها بنجاح']);
    }

    // ✅ تصدير الطلبات الخاصة بالبائعين الموافق عليها فقط
    public function exportApprovedSellerOrders()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['ID', 'User ID', 'Seller ID', 'Total Price', 'Status', 'City', 'Governorate', 'Street', 'Phone', 'Payment Method', 'Approval Status'];
        $sheet->fromArray($headers, null, 'A1');

        // الطلبات التي تحتوي على seller_id وتمت الموافقة عليها
        $orders = Order::whereNotNull('seller_id')
            ->where('approval_status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        $row = 2;

        foreach ($orders as $order) {
            $sheet->fromArray([
                $order->id,
                $order->user_id,
                $order->seller_id,
                $order->total_price,
                $order->status,
                $order->city,
                $order->governorate,
                $order->street,
                $order->phone,
                $order->payment_method,
                $order->approval_status,
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'approved-seller-orders-' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);
        (new Xlsx($spreadsheet))->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
}

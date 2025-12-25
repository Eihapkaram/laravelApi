<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Notifications\SupplierOrderCreated;
use App\Notifications\SupplierOrderResponded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;

class SupplierOrderController extends Controller
{
    /**
     * إنشاء طلب تجهيز من الأدمن للمورد
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.supplier_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // تأكد إن اللي بينفذ أدمن
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        DB::beginTransaction();

        try {
            // إنشاء الطلب
            $order = SupplierOrder::create([
                'supplier_id' => $request->supplier_id,
                'created_by' => auth()->id(),
                'status' => 'sent',
                'notes' => $request->notes,
                'total_price' => 0,
            ]);

            $total = 0;

            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['supplier_price'];

                SupplierOrderItem::create([
                    'supplier_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'supplier_price' => $item['supplier_price'],
                    'total_price' => $itemTotal,
                ]);

                $total += $itemTotal;
            }

            $order->update(['total_price' => $total]);

            DB::commit();
            // إشعار المورد
            $order->supplier->notify(new SupplierOrderCreated($order));

            return response()->json([
                'message' => 'تم إنشاء طلب التجهيز بنجاح',
                'order' => $order->load('items.product', 'supplier'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض طلبات المورد (للمورد نفسه)
     */
    public function supplierOrders()
    {
        $orders = SupplierOrder::where('supplier_id', auth()->id())
            ->with('items.product', 'creator')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * تغيير حالة الطلب (المورد)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:preparing,ready,received,cancelled',
        ]);

        $order = SupplierOrder::where('id', $id)
            ->where('supplier_id', auth()->id())
            ->firstOrFail();

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'تم تحديث حالة الطلب',
            'order' => $order,
        ]);
    }

    public function accept($id)
    {
        $order = SupplierOrder::where('id', $id)
            ->where('supplier_id', auth()->id())
            ->where('status', 'sent')
            ->firstOrFail();

        $order->update([
            'status' => 'preparing',
            'responded_at' => now(),
        ]);
        $order->creator->notify(new SupplierOrderResponded($order));

        return response()->json([
            'message' => 'تم قبول طلب التجهيز بنجاح',
            'order' => $order,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        $order = SupplierOrder::where('id', $id)
            ->where('supplier_id', auth()->id())
            ->where('status', 'sent')
            ->firstOrFail();

        $order->update([
            'status' => 'cancelled',
            'responded_at' => now(),
            'supplier_reject_reason' => $request->reason,
        ]);
        $order->creator->notify(new SupplierOrderResponded($order));

        return response()->json([
            'message' => 'تم رفض الطلب',
            'order' => $order,
        ]);
    }

    public function generateSupplierOrderInvoice($id)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $order = SupplierOrder::with('items.product', 'supplier', 'creator')->findOrFail($id);

        // التأكد إن المستخدم يحق له الوصول: المورد نفسه أو الأدمن اللي أنشأ الطلب
        $user = auth()->user();
        if (
            $user->id !== $order->supplier_id &&
            $user->id !== $order->created_by &&
            $user->role !== 'admin'
        ) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $settings = Setting::first();
        $logoPath = $settings && $settings->logo ? public_path('storage/'.$settings->logo) : null;

        $html = '
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "dejavusans", sans-serif; text-align: right; direction: rtl; font-size: 14px; color: #333; }
            h3 { margin-bottom: 5px; color: #1e3a8a; }
            table { border-collapse: collapse; width: 100%; margin-top: 15px; font-size: 13px; }
            th, td { border: 1px solid #000; padding: 10px; text-align: center; }
            th { background-color: #f3f4f6; color: #111827; }
            tbody tr:nth-child(even) { background-color: #f9fafb; }
            .total { font-weight: bold; font-size: 15px; color: #1e40af; }
            .signature { margin-top: 40px; font-size: 16px; font-weight: bold; color: #1e3a8a; }
        </style>
    </head>
    <body>
        <div align="center">'
            .(file_exists($logoPath) ? '<img src="'.$logoPath.'" width="120">' : '').'
            <h3>فاتورة طلب المورد</h3>
        </div>

        <p><strong>رقم الطلب:</strong> '.$order->id.'</p>
        <p><strong>تاريخ الطلب:</strong> '.$order->created_at->format('Y-m-d').'</p>
        <p><strong>حالة الطلب:</strong> '.$order->status.'</p>

        <h4>معلومات المورد</h4>
        <p><strong>الاسم:</strong> '.$order->supplier->name.'</p>
        <p><strong>الهاتف:</strong> '.$order->supplier->phone.'</p>
        <p><strong>البريد الإلكتروني:</strong> '.$order->supplier->email.'</p>

        <table>
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>سعر المورد</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($order->items as $item) {
            $html .= '<tr>
            <td>'.$item->product->titel.'</td>
            <td>'.$item->quantity.'</td>
            <td>'.number_format(round($item->supplier_price), 2).'</td>
            <td>'.number_format(round($item->total_price), 2).'</td>
        </tr>';
        }

        $html .= '
            </tbody>
        </table>

        <p class="total"><strong>المجموع الكلي:</strong> '.number_format(round($order->total_price), 2).'</p>

        <div class="signature" align="left">
            <p>توقيع الشركة:</p>
            '.($settings && $settings->site_name ? '<strong>'.$settings->site_name.'</strong>' : '').'
        </div>
    </body>
    </html>';

        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
        ]);

        $mpdf->WriteHTML($html);

        return response()->streamDownload(function () use ($mpdf) {
            echo $mpdf->Output('', 'S');
        }, 'supplier-order-'.$order->id.'.pdf');
    }

    public function downloadAllInvoices($supplierId)
    {
        $user = auth()->user();

        // فقط الأدمن أو المورد نفسه
        if ($user->role !== 'admin' && $user->id != $supplierId) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $orders = SupplierOrder::where('supplier_id', $supplierId)
            ->with('items.product', 'supplier')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'لا توجد طلبات لهذا المورد'], 404);
        }

        $zip = new \ZipArchive;
        $zipName = 'supplier_orders_'.$supplierId.'.zip';
        $zipPath = storage_path('app/public/'.$zipName);

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'فشل في إنشاء ملف مضغوط'], 500);
        }

        foreach ($orders as $order) {
            $pdfContent = $this->generateSupplierOrderInvoiceHtml($order); // ترجع المحتوى HTML
            $mpdf = new \Mpdf\Mpdf(['tempDir' => storage_path('app/mpdf_temp'), 'mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans']);
            $mpdf->WriteHTML($pdfContent);
            $pdfData = $mpdf->Output('', 'S');

            $zip->addFromString("order_{$order->id}.pdf", $pdfData);
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\SupplierOrderCreated;
use App\Notifications\SupplierOrderResponded;
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

        // ✅ تحقق role = admin
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        DB::beginTransaction();

        try {
            $order = SupplierOrder::create([
                'supplier_id' => $request->supplier_id,
                'created_by'  => auth()->id(),
                'status'      => 'sent',
                'notes'       => $request->notes,
                'total_price' => 0,
            ]);

            $total = 0;

            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['supplier_price'];

                SupplierOrderItem::create([
                    'supplier_order_id' => $order->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'supplier_price'    => $item['supplier_price'],
                    'total_price'       => $itemTotal,
                ]);

                $total += $itemTotal;
            }

            $order->update(['total_price' => $total]);

            DB::commit();

            // إشعار المورد
            $order->supplier->notify(new SupplierOrderCreated($order));

            return response()->json([
                'message' => 'تم إنشاء طلب التجهيز بنجاح',
                'order'   => $order->load('items.product', 'supplier'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ',
                'error'   => $e->getMessage()
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
            'order' => $order
        ]);
    }

    /**
     * قبول الطلب (المورد)
     */
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
            'order' => $order
        ]);
    }

    /**
     * رفض الطلب (المورد)
     */
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
            'order' => $order
        ]);
    }

    /**
     * تحميل فاتورة الطلب PDF
     */
    public function generateSupplierOrderInvoice($id)
    {
        $order = SupplierOrder::with('items.product', 'supplier', 'creator')->findOrFail($id);
        $user = auth()->user();

        // ✅ role = admin
        if (
            $user->id !== $order->supplier_id &&
            $user->id !== $order->created_by &&
            $user->role !== 'admin'
        ) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $html = view('pdf.supplier-order', compact('order'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans'
        ]);

        $mpdf->WriteHTML($html);

        return response()->streamDownload(
            fn () => print($mpdf->Output('', 'S')),
            'supplier-order-' . $order->id . '.pdf'
        );
    }

    /**
     * تحميل كل فواتير مورد
     */
    public function downloadAllInvoices($supplierId)
    {
        $user = auth()->user();

        // ✅ role = admin
        if ($user->role !== 'admin' && $user->id != $supplierId) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $orders = SupplierOrder::where('supplier_id', $supplierId)
            ->with('items.product')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'لا توجد طلبات'], 404);
        }

        // باقي الكود زي ما هو عندك
    }
}

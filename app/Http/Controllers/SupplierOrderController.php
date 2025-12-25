<?php

namespace App\Http\Controllers;

use App\Models\SupplierOrder;
use App\Models\SupplierOrderItem;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Mpdf\Mpdf;

class SupplierOrderController extends Controller
{
    // إنشاء طلب جديد
    public function create(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:users,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $order = SupplierOrder::create([
            'supplier_id' => $request->supplier_id,
            'created_by' => auth()->id(),
            'total_price' => collect($request->items)->sum(fn($i) => $i['quantity'] * $i['price']),
            'status' => 'sent',
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $item) {
            $order->items()->create($item);
        }

        // إشعار المورد
        $supplier = User::find($request->supplier_id);
        $supplier->notify(new \App\Notifications\NewOrderForSupplierNotification($order, auth()->user()));

        return response()->json([
            'message' => 'تم إنشاء طلب المورد بنجاح',
            'order' => $order->load('items.product', 'supplier', 'creator'),
        ], 201);
    }

    // المورد يوافق على الطلب
    public function approve($id)
    {
        $order = SupplierOrder::findOrFail($id);
        $order->update([
            'status' => 'preparing',
            'responded_at' => now(),
        ]);

        // إشعار الأدمن
        $order->creator->notify(new \App\Notifications\OrderApprovedNotification($order, auth()->user()));

        return response()->json(['message' => 'تم قبول الطلب']);
    }

    // المورد يرفض الطلب
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        $order = SupplierOrder::findOrFail($id);
        $order->update([
            'status' => 'cancelled',
            'supplier_reject_reason' => $request->reason,
            'responded_at' => now(),
        ]);

        // إشعار الأدمن
        $order->creator->notify(new \App\Notifications\OrderRejectedNotification($order, auth()->user()));

        return response()->json(['message' => 'تم رفض الطلب']);
    }

    // تنزيل فاتورة PDF
    public function generateInvoice($id)
    {
        $order = SupplierOrder::with('items.product', 'supplier', 'creator')->findOrFail($id);
        $settings = Setting::first();

        $logoPath = $settings && $settings->logo
            ? public_path('storage/' . $settings->logo)
            : null;

        $html = view('pdf.supplier_invoice', compact('order', 'settings', 'logoPath'))->render();

        $mpdf = new Mpdf(['tempDir' => storage_path('app/mpdf_temp'), 'mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans']);
        $mpdf->WriteHTML($html);

        return response()->streamDownload(function () use ($mpdf) {
            echo $mpdf->Output('', 'S');
        }, 'supplier-invoice-' . $order->id . '.pdf');
    }
}

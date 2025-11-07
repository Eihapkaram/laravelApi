<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UnifiedPaymobService;

class PaymentController extends Controller
{
    private $paymob;

    public function __construct(UnifiedPaymobService $paymob)
    {
        $this->paymob = $paymob;
    }
$cart = $user->getcart()->with('proCItem.product')->first();

        if (!$cart || $cart->proCItem->isEmpty()) {
            return response()->json(['message' => 'السلة فارغة'], 400);
        }

        $total = $cart->proCItem->sum(fn($item) => $item->quantity * $item->product->price);

    // إنشاء الدفع
    public function pay(Request $request)
    {
        $amount = $total * 100; // تحويل جنيهات إلى قرش

        // بيانات billing كاملة
        $billingData = [
            "first_name"    => $request->first_name ?? "NA",
            "last_name"     => $request->last_name ?? "NA",
            "email"         => $request->email ?? "example@test.com",
            "phone_number"  => $request->phone_number ?? "NA",
            "country"       => $request->country ?? "EG",
            "city"          => $request->city ?? "NA",
            "street"        => $request->street ?? "NA",
        ];

        $response = $this->paymob->createIntention($amount, $billingData);

        // التحقق بعدم وجود client_secret
        if (empty($response) || !isset($response['client_secret'])) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to create payment intention',
                'response' => $response
            ], 500);
        }

        $clientSecret = $response['client_secret'];

        // الحصول على رابط الدفع
        $checkoutUrl = $this->paymob->getCheckoutUrl($clientSecret);

        return response()->json([
            'error' => false,
            'message' => 'Payment intention created successfully',
            'checkout_url' => $checkoutUrl,
            'client_secret' => $clientSecret,
            'response' => $response, // لإضافة التفاصيل في الواجهة
        ]);
    }

    // Webhook لاستقبال تفاصيل الدفع
    public function webhook(Request $request)
    {
        \Log::info('Paymob Webhook:', $request->all());
        // حفظ بيانات الدفع في قاعدة البيانات إذا أردت
        return response()->json(['status' => 'received']);
    }

    // Redirect بعد اكتمال الدفع
    public function redirect(Request $request)
    {
        $data = $request->all();
        $status = $data['success'] ?? false;

        $message = $status
            ? "✅ تم الدفع بنجاح!"
            : "❌ فشل الدفع، يرجى المحاولة مرة أخرى.";

        // يمكنك حفظ بيانات الدفع هنا إذا أردت

        return response()->json([
            'message' => $message,
            'status' => $status,
            'data' => $data
        ]);
    }
}

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

    // إنشاء الدفع
    public function pay(Request $request)
    {
        $amount = $request->amount * 100; // تحويل جنيهات إلى قرش
        $billingData = [
            "first_name" => $request->first_name ?? "Ahmed",
            "last_name" => $request->last_name ?? "Ali",
            "email" => $request->email ?? "ahmed@example.com",
            "phone_number" => $request->phone_number ?? "201234567890",
        ];

        $response = $this->paymob->createIntention($amount, $billingData);

        if (!isset($response['intention_detail']['client_secret'])) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to create payment intention',
                'response' => $response
            ], 500);
        }

        $clientSecret = $response['intention_detail']['client_secret'];
        $checkoutUrl = $this->paymob->getCheckoutUrl($clientSecret);

        return response()->json([
            'checkout_url' => $checkoutUrl,
            'client_secret' => $clientSecret,
        ]);
    }

    // Webhook لاستقبال تفاصيل الدفع
    public function webhook(Request $request)
    {
        \Log::info('Paymob Webhook:', $request->all());
        // يمكنك حفظ البيانات في قاعدة البيانات هنا
        return response()->json(['status' => 'received']);
    }

    // Redirect بعد اكتمال الدفع
    public function redirect(Request $request)
    {
        $data = $request->all();
        $status = $data['success'] ?? false;

        if ($status) {
            $message = "✅ تم الدفع بنجاح!";
        } else {
            $message = "❌ فشل الدفع، يرجى المحاولة مرة أخرى.";
        }

        // يمكنك حفظ بيانات الدفع هنا إذا أردت

        return response()->json([
            'message' => $message,
            'status' => $status,
            'data' => $data
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
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
         $user = auth()->user();

      $cart = $user->getcart()->with('proCItem.product')->first();

        if (!$cart || $cart->proCItem->isEmpty()) {
            return response()->json(['message' => 'السلة فارغة'], 400);
        }

        $total = $cart->proCItem->sum(fn($item) => $item->quantity * $item->product->price);

        $amount = $total * 100; // تحويل جنيهات إلى قرش

        // بيانات billing كاملة
        $billingData = [
            "first_name"    => $user->first_name ?? "NA",
            "last_name"     => $user->last_name ?? "NA",
            "email"         => $user->email ?? "example@test.com",
            "phone_number"  => $user->phone ?? "NA",
            "country"       => $user->country ?? "EG",
            "city"          => $user->city ?? "NA",
            "street"        => $user->street ?? "NA",
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

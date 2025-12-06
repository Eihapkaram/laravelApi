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
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
        $cart = $user->getcart()->with('proCItem.product')->first();

        if (!$cart || $cart->proCItem->isEmpty()) {
            return response()->json(['message' => 'السلة فارغة'], 400);
        }

        $total = $cart->proCItem->sum(fn($item) => $item->quantity * $item->product->price);
        \Log::info("Total before payment: {$total}");

        $amount = $total * 100;
        \Log::info("Amount sent to Paymob: {$amount}");

        // بيانات billing كاملة
        $billingData = [
            "first_name"    => $request->first_name ?? "h",
            "last_name"     => $user->last_name ?? "h",
            "email"         => $user->email ?? "exampel@rt.gmail.com",
            "phone_number"  => $request->phone_number  ?? $user->phone,
            "country"       =>  $request->country ?? "eg",
            "city"          =>  $request->city ?? "cairo",
            "street"        => $request->street ?? "street",
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
        $data = $request->all();

    // Paymob transaction object
    $transaction = $data['obj'] ?? null;

    if (!$transaction) {
        \Log::error('No transaction object in webhook');
        return response()->json(['error' => 'invalid webhook'], 400);
    }

    $success = $transaction['success'] ?? false;
    $pending = $transaction['pending'] ?? false;
    $orderId = $transaction['order']['id'] ?? null;
    $merchantOrderId = $transaction['order']['merchant_order_id'] ?? null;

    \Log::info('Paymob Transaction:', $transaction);

    return response()->json([
        'status' => $success ? 'paid' : ($pending ? 'pending' : 'failed'),
        'order_id' => $orderId,
        'merchant_order_id' => $merchantOrderId
    ]);
    }

    // Redirect بعد اكتمال الدفع
    public function redirect(Request $request)
    {
        $success = filter_var($request->input('success', false), FILTER_VALIDATE_BOOLEAN);
    $txnCode = $request->input('txn_response_code');

    // الدفع ناجح لو:
    $isPaid = ($success && ($txnCode === 'APPROVED' || $txnCode === '00'));

    if ($isPaid) {
        return redirect('/paymob/success.html');
    } else {
        return redirect('/paymob/failed.html');
    }
    }
   

}

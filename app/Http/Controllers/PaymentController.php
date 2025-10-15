<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PaymobService;

class PaymentController extends Controller
{
    private $paymob;

    public function __construct(PaymobService $paymob)
    {
        $this->paymob = $paymob;
    }

    public function pay(Request $request)
    {
        $token = $this->paymob->authenticate();

        // المنتجات جاية من الـ request كـ Array جاهزة
       $items = $request->items; 

        $order = $this->paymob->createOrder(
            $token,
            $request->amount,
            'EGP',
            $items,
        );

        if (!isset($order['id'])) {
            return response()->json([
                'error' => true,
                'message' => 'Order creation failed',
                'response' => $order
            ], 500);
        }

        $billingData = [
            "apartment"     => "NA",
            "email"         => $request->email ?? "example@test.com",
            "floor"         => "NA",
            "first_name"    => $request->first_name ?? "NA",
            "street"        => $request->street ?? "NA",
            "building"      => "NA",
            "phone_number"  => $request->phone_number ?? "NA",
            "shipping_method" => "NA",
            "postal_code"   => "NA",
            "city"          => $request->city ?? "NA",
            "country"       => $request->country ?? "EG",
            "last_name"     => $request->last_name ?? "NA",
            "state"         => "NA"
        ];

        $paymentKey = $this->paymob->generatePaymentKey(
            $token,
            $order['id'],
            $request->amount,
            $billingData
        );

        $iframeUrl = $this->paymob->getIframeUrl($paymentKey);

        return response()->json([
            'url'   => $iframeUrl,
            'order' => $order,
            'items' => $items,
        ]);
    }
}

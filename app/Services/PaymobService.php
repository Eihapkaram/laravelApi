<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaymobService
{
    private $apiKey;
    private $integrationId;
    private $iframeId;


    public function __construct()
    {
        $this->apiKey = config('services.paymob.api_key');
        $this->integrationId = config('services.paymob.integration_id');
        $this->iframeId = config('services.paymob.iframe_id');
    }

    // 1. Generate Authentication Token
    public function authenticate()
    {
        $response = Http::post('https://accept.paymob.com/api/auth/tokens', [
            'api_key' => $this->apiKey,
        ]);

        return $response->json('token');
    }

    // 2. Register Order
    public function createOrder($token, $amount, $currency = 'EGP', $items = [])
    {
        $response = Http::post('https://accept.paymob.com/api/ecommerce/orders', [
            'auth_token' => $token,
            'delivery_needed' => 'false',
            'amount_cents' => $amount * 100, // amount in cents
            'currency' => $currency,
            'items' => $items
        ]);

        return $response->json();
    }

    // 3. Generate Payment Key
    public function generatePaymentKey($token, $orderId, $amount, $billingData, $currency = 'EGP')
    {
        $response = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
            "auth_token" => $token,
            "amount_cents" => $amount * 100,
            "expiration" => 3600,
            "order_id" => $orderId,
            "billing_data" => $billingData,
            "currency" => $currency,
            "integration_id" => $this->integrationId,
        ]);

        return $response->json('token');
    }

    // 4. Get Iframe URL
    public function getIframeUrl($paymentToken)
    {
        return "https://accept.paymob.com/api/acceptance/iframes/{$this->iframeId}?payment_token={$paymentToken}";
    }
}

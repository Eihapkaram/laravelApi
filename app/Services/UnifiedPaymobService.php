<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class UnifiedPaymobService
{
    private $baseUrl;
    private $secretKey;
    private $publicKey;

    public function __construct()
    {
        $this->baseUrl = env('PAYMOB_BASE_URL', 'https://accept.paymob.com');
        $this->secretKey = env('PAYMOB_SECRET_KEY');
        $this->publicKey = env('PAYMOB_PUBLIC_KEY');
    }

    public function createIntention($amountCents, $billingData)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $this->secretKey,
            'Content-Type'  => 'application/json',
        ])->post($this->baseUrl . '/v1/intention/', [
            "amount" => $amountCents,
            "currency" => "EGP",
            "payment_methods" => [
                (int) env('PAYMOB_INTEGRATION_CARD'),
                (int) env('PAYMOB_INTEGRATION_WALLET'),
            ],
            "billing_data" => $billingData,
            "notification_url" => url('/paymob/webhook'),
            "redirection_url" => url('/paymob/redirect'),
            "special_reference" => "ORDER-" . uniqid(),
        ]);

        return $response->json();
    }

    public function getCheckoutUrl($clientSecret)
    {
        return $this->baseUrl . '/unifiedcheckout/?publicKey=' .
            $this->publicKey . '&clientSecret=' . $clientSecret;
    }
}

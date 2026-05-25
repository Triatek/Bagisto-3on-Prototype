<?php

namespace Triatek\Doku\Payment;

use Webkul\Payment\Payment\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Webkul\Sales\Repositories\OrderRepository;
use Illuminate\Support\Str;

class Doku extends Payment
{
    protected $code = 'doku';


    /**
     * Memaksa Bagisto memberikan judul default jika kosong di database
     */
    public function getTitle()
    {
        $title = $this->getConfigData('title');
        
        // Jika title dari database kosong, gunakan teks statis ini
        return !empty($title) ? $title : 'Doku Payment Gateway';
    }

    public function getImage()
    {
        $image = $this->getConfigData('image');
        
        if ($image) {
            return \Illuminate\Support\Facades\Storage::url($image);
        }
        
        return null;
    }
    public function getRedirectUrl()
    {
        $order = app(OrderRepository::class)->find(session('order_id'));

        if (! $order) {
            return route('shop.checkout.cart.index');
        }

        $clientId = core()->getConfigData('sales.payment_methods.doku.client_id');
        $secretKey = core()->getConfigData('sales.payment_methods.doku.secret_key');
        $isSandbox = core()->getConfigData('sales.payment_methods.doku.sandbox');

        $targetPath = '/checkout/v1/payment';
        $baseUrl = $isSandbox ? 'https://api-sandbox.doku.com' : 'https://api.doku.com';

        // Menyusun Payload berdasarkan Panduan Integrasi Doku
        $payload = [
            "order" => [
                "amount"         => intval(floatval($order->grand_total)),
                "invoice_number" => $order->id . '-' . time(),
                "currency"       => "IDR"
            ],
            "payment" => [
                "payment_due_date" => 60 
            ],
            "customer" => [
                "id"    => (string) ($order->customer_id ?? 'guest_' . time()),
                "name"  => $order->customer_first_name . ' ' . $order->customer_last_name,
                "email" => $order->customer_email,
                "phone" => $order->customer_phone ?? '081234567890',
            ]
        ];

        $requestId = (string) Str::uuid();
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        $signature = $this->generateSignature($clientId, $requestId, $timestamp, $targetPath, $payload, $secretKey);

        try {
            $response = Http::withHeaders([
                'Client-Id'         => $clientId,
                'Request-Id'        => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature'         => $signature,
                'Content-Type'      => 'application/json'
            ])->post($baseUrl . $targetPath, $payload);

            $result = $response->json();

            if (isset($result['message'][0]) && $result['message'][0] === 'SUCCESS') {
                return $result['response']['payment']['url'];
            }

            Log::error("Doku API Error: " . json_encode($result));
            return route('shop.checkout.cart.index');

        } catch (\Exception $e) {
            Log::error("Doku Exception: " . $e->getMessage());
            return route('shop.checkout.cart.index');
        }
    }

    private function generateSignature($clientId, $requestId, $timestamp, $targetPath, $payload, $secretKey)
    {
        $jsonPayload = json_encode($payload);
        $digest = base64_encode(hash('sha256', $jsonPayload, true));

        $components = [
            "Client-Id:" . $clientId,
            "Request-Id:" . $requestId,
            "Request-Timestamp:" . $timestamp,
            "Request-Target:" . $targetPath,
            "Digest:" . $digest
        ];

        $stringToSign = implode("\n", $components);
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $secretKey, true));

        return "HMACSHA256=" . $signature;
    }
}
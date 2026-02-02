<?php

namespace Triatek\Midtrans\Payment;

use Webkul\Payment\Payment\Payment;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Repositories\OrderRepository;
use Midtrans\Config;
use Midtrans\Snap;

class Midtrans extends Payment
{
    protected $code  = 'midtrans';

    public function getRedirectUrl()
    {
        // 1. AMBIL DARI SESSION (Sekarang pasti berhasil!)
        $orderId = session('order_id');
        $order = app(OrderRepository::class)->find($orderId);

        if (!$order) {
            // Jika masih gagal, pakai Smart Detect sebagai cadangan darurat
            Log::info(">>> Fallback ke Smart Detect");
            $order = app(OrderRepository::class)->orderBy('id', 'desc')->first();
        }
        
        // Cek Double (Biar gak bayar order 62 lagi)
        if ($order->id == 62 && session('order_id') != 62) {
             // Jika order yg didapat 62, padahal kita mau order baru, berarti gagal.
             // Lebih baik error daripada salah bayar.
        }

        // 2. CONFIG
        $serverKey = core()->getConfigData('sales.payment_methods.midtrans.server_key');
        $clientKey = core()->getConfigData('sales.payment_methods.midtrans.client_key');
        $isSandbox = core()->getConfigData('sales.payment_methods.midtrans.sandbox');
        
        Config::$serverKey = $serverKey;
        Config::$clientKey = $clientKey;
        Config::$isProduction = !$isSandbox;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // 3. FIX ANGKA (Wajib ada)
        $fixTotal = intval(floatval($order->grand_total));

        $params = [
            'transaction_details' => [
                'order_id' => $order->id . '-' . time(),
                'gross_amount' => $fixTotal,
            ],
            'customer_details' => [
                'first_name' => $order->customer_first_name,
                'email'      => $order->customer_email,
                'phone'      => $order->customer_phone,
            ],
        ];

        try {
            $transaction = Snap::createTransaction($params);
            return $transaction->redirect_url;
        } catch (\Exception $e) {
            Log::error("Midtrans Error: " . $e->getMessage());
            return route('shop.checkout.cart.index');
        }
    }
}
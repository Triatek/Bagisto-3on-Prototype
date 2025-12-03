<?php

namespace Triatek\RajaOngkir\Carriers;

use Config;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart; 

class RajaOngkir extends AbstractShipping
{
    protected $code = 'rajaongkir';
    public $rates = [];

    public function isAvailable()
    {
        return true;
    }

    public function calculate()
    {
        $cart = Cart::getCart();
        if (! $cart) return false;

        // Setting API
        $apiKey = 'P4mpsCWya3a97dfb92531eebI81HdUUH'; 
        $origin = 152; 
        $destinationId = 152; 

        $totalWeight = 0;
        foreach ($cart->items as $item) {
            $totalWeight += $item->total_weight > 0 ? $item->total_weight : 1000; 
        }

        try {
            $url = 'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost';

            $response = Http::withoutVerifying()->asForm()->withHeaders([
                'key' => $apiKey
            ])->post($url, [
                'origin'          => $origin,
                'originType'      => 'city', 
                'destination'     => $destinationId,
                'destinationType' => 'city', 
                'weight'          => $totalWeight * 1000, 
                'courier'         => 'jne',
            ]);

            $body = $response->json();

            if (isset($body['data']) && !empty($body['data'])) {
                foreach ($body['data'] as $cost) {
                    $object = new CartShippingRate;
                    $object->carrier = 'rajaongkir';
                    $object->carrier_title = 'JNE RajaOngkir'; 
                    $object->method = 'rajaongkir_' . $cost['service'];
                    $object->method_title = 'JNE ' . $cost['service'];
                    $object->method_description = $cost['description'] . ' (' . $cost['etd'] . ')';
                    $object->price = $cost['cost'];
                    $object->base_price = $cost['cost'];
                    
                    // Masukkan ke array rates lokal
                    $this->rates[] = $object;
                }
            }

        } catch (\Exception $e) {
            Log::error('RAJAONGKIR ERROR: ' . $e->getMessage());
        }

        // 👇👇👇 INI KUNCI YANG HILANG DARI TADI! 👇👇👇
        // Kita WAJIB mengembalikan (return) data ini ke Bagisto.
        return $this->rates;
    }
}
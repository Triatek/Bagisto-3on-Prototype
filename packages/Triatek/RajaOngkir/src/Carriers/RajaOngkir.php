<?php

namespace Triatek\RajaOngkir\Carriers;

use Config;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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

        // AMBIL API KEY DARI CONFIG ATAU HARDCODE
        $apiKey = $this->getConfigData('api_key');
        if (!$apiKey) $apiKey = 'P4mpsCWya3a97dfb92531eebI81HdUUH'; 
        
        $origin = $this->getConfigData('origin_city');
        if (!$origin) $origin = 152;

        $shippingAddress = $cart->shipping_address;
        if (!$shippingAddress || !$shippingAddress->city) {
            return false;
        }

        // CARI ID KOTA TUJUAN
        // Kita kirim $apiKey ke fungsi ini
        $destinationId = $this->getCityId($shippingAddress->city, $apiKey);

        if (!$destinationId) {
            // Jika tidak ketemu, fallback ke Jakarta Pusat (152) biar tidak error
            // Tapi harganya bakal salah (harga Jakarta)
            Log::warning("RAJAONGKIR: Kota tidak ketemu! Fallback ke 152");
            $destinationId = 152; 
        }

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
                    $this->rates[] = $object;
                }
            }

        } catch (\Exception $e) {
            Log::error('RAJAONGKIR ERROR: ' . $e->getMessage());
        }

        return $this->rates;
    }

    private function getCityId($cityName, $apiKey)
    {
        $cleanCityName = trim(str_ireplace(['Kota', 'Kabupaten', 'Kab.'], '', $cityName));
        
        // Matikan Cache Dulu Saat Testing
        // $cacheKey = 'city_id_' . Str::slug($cleanCityName);
        // if (Cache::has($cacheKey)) return Cache::get($cacheKey);

        try {
            $url = 'https://rajaongkir.komerce.id/api/v1/destination/domestic-destination';
            
            // 👇👇👇 PERBAIKAN DISINI 👇👇👇
            // Kita pindahkan KEY ke HEADER, bukan di array get()
            $response = Http::withoutVerifying()
                ->withHeaders(['key' => $apiKey]) // <--- SOLUSI 401
                ->get($url, [
                    'search' => $cleanCityName
                ]);

            $data = $response->json();

            // Log hasil pencarian biar kita yakin
            Log::info("RAJAONGKIR SEARCH: " . $cleanCityName, $data);

            if (isset($data['data']) && count($data['data']) > 0) {
                $foundId = $data['data'][0]['id'];
                // Cache::put($cacheKey, $foundId, 1440);
                return $foundId;
            }
        } catch (\Exception $e) {
            Log::error("RAJAONGKIR SEARCH ERROR: " . $e->getMessage());
            return null;
        }

        return null;
    }
}
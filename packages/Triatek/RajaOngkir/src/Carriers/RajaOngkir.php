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

        // 1. AMBIL ALAMAT TUJUAN
        $shippingAddress = $cart->shipping_address;

        if (!$shippingAddress || !$shippingAddress->city) {
            return false;
        }

        // ==================================================================
        // KONFIGURASI (DARI ADMIN PANEL / ENV)
        // ==================================================================
        
        // 1. Cek di Admin Panel -> Configure -> Sales -> Shipping Methods -> RajaOngkir
        // 2. Jika kosong, cek file .env
        $apiKey = core()->getConfigData('sales.carriers.rajaongkir.api_key') ?: env('RAJAONGKIR_API_KEY');
        $origin = core()->getConfigData('sales.carriers.rajaongkir.origin_city') ?: env('RAJAONGKIR_ORIGIN_ID');

        // Validasi: Jika konfigurasi belum diisi, stop proses agar tidak error
        if (empty($apiKey) || empty($origin)) {
            // Opsional: Log error untuk debugging
            // Log::error('RajaOngkir: API Key atau Origin belum disetting di Admin/Env.');
            return false;
        }

        // ==================================================================
        
        // 2. CARI ID KOTA TUJUAN
        $destinationId = $this->getCityId($shippingAddress->city, $apiKey);

        if (!$destinationId) {
            return false;
        }

        $listKurir = [
            'jne', 
            'pos', 
            'tiki', 
            'sicepat', 
            'jnt', 
            'anteraja'
        ];

        // --- HITUNG BERAT ---
        $totalWeight = 0;
        foreach ($cart->items as $item) {
            $beratItem = $item->total_weight > 0 ? $item->total_weight : 1; 
            $totalWeight += $beratItem;
        }
        $weightInGrams = $totalWeight * 1000;
        // -----------------------

        foreach ($listKurir as $kurir) {
            try {
                $url = 'https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost';

                $response = Http::withoutVerifying()->asForm()->withHeaders([
                    'key' => $apiKey
                ])->post($url, [
                    'origin'          => $origin,
                    'originType'      => 'city', 
                    'destination'     => $destinationId,
                    'destinationType' => 'city', 
                    'weight'          => $weightInGrams, 
                    'courier'         => $kurir, 
                ]);

                $body = $response->json();

                if (isset($body['data']) && !empty($body['data'])) {
                    foreach ($body['data'] as $cost) {
                        if ($cost['cost'] <= 0) continue;

                        // ======================================================
                        // BAGIAN FILTER: HANYA REGULER (BLACKLIST CARGO)
                        // ======================================================
                        
                        $serviceCode = strtoupper($cost['service']); 
                        
                        // DAFTAR BLACKLIST 
                        $blockedServices = [
                            'JTR', 'TRUCKING', 'GOKIL', 'CARGO', 'ECO', 'HALU', 'OKE'
                        ];

                        if (in_array($serviceCode, $blockedServices)) {
                            continue; 
                        }

                        if (str_contains(strtoupper($cost['description']), 'CARGO') || str_contains(strtoupper($cost['description']), 'TRUCK')) {
                            continue;
                        }

                        // ======================================================
                        // AKHIR FILTER
                        // ======================================================

                        $object = new CartShippingRate;
                        $object->carrier = 'rajaongkir';
                        
                        $imgUrl = asset('images/' . $kurir . '.png');
                        $logoHtml = "<img src='$imgUrl' style='height: 30px; width: auto ; display: block; margin-bottom: 5px;'>";

                        $object->carrier_title = $logoHtml;
                        $object->method = 'rajaongkir_' . $kurir . '_' . $cost['service'];
                        $object->method_title = strtoupper($kurir) . ' - ' . $cost['service']; 
                        $object->method_description = $cost['description'] . ' (' . $cost['etd'] . ' hari)';
                        $object->price = $cost['cost'];
                        $object->base_price = $cost['cost'];
                        
                        $this->rates[] = $object;
                    }
                }

            } catch (\Exception $e) {
                continue;
            }
        }

        return $this->rates;
    }

    // --- FUNGSI PENCARI ID KOTA ---
    private function getCityId($cityName, $apiKey)
    {
        return Cache::remember('city_id_' . Str::slug($cityName), 60 * 24, function () use ($cityName, $apiKey) {
            try {
                $response = Http::withoutVerifying()->withHeaders([
                    'key' => $apiKey
                ])->get('https://rajaongkir.komerce.id/api/v1/destination/domestic-destination', [
                    'search' => $cityName 
                ]);

                $body = $response->json();

                if (isset($body['data']) && count($body['data']) > 0) {
                    return $body['data'][0]['id']; 
                }

            } catch (\Exception $e) {
                return null;
            }
            return null;
        });
    }
}
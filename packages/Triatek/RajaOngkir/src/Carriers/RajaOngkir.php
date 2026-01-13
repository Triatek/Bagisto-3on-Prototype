<?php

namespace Triatek\RajaOngkir\Carriers;

use Config;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // Wajib Import Cache
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

        // 1. AMBIL ALAMAT TUJUAN DARI CART (DINAMIS)
        $shippingAddress = $cart->shipping_address;

        // Jika user belum isi alamat, stop proses (biar gak error)
        if (!$shippingAddress || !$shippingAddress->city) {
            return false;
        }

        // --- KONFIGURASI ---
        $apiKey = 'P4mpsCWya3a97dfb92531eebI81HdUUH'; 
        $origin = 23; // ID Kota Toko (Bandung)
        
        // 2. CARI ID KOTA TUJUAN BERDASARKAN NAMA KOTA
        // Bagisto ngasih string "Bandung", kita cari ID-nya (23) pakai fungsi di bawah
        $destinationId = $this->getCityId($shippingAddress->city, $apiKey);

        // Kalau ID Kota tidak ketemu di database RajaOngkir, skip
        if (!$destinationId) {
            return false;
        }

       $listKurir = [
    'jne', 
    'pos', 
    'tiki', 
    'sicepat', 
    'jnt', 
    'ninja', 
    'lion', 
    'anteraja'
];

        // --- PERBAIKAN BERAT (THE 1 TON FIX) ---
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
                    'destination'     => $destinationId, // <--- SUDAH DINAMIS SEKARANG
                    'destinationType' => 'city', 
                    'weight'          => $weightInGrams, 
                    'courier'         => $kurir, 
                ]);

                $body = $response->json();

                if (isset($body['data']) && !empty($body['data'])) {
                    foreach ($body['data'] as $cost) {
                        if ($cost['cost'] <= 0) continue;

                        $object = new CartShippingRate;
                        $object->carrier = 'rajaongkir';
                        
                        $imgUrl = asset('images/' . $kurir . '.png');
                        $logoHtml = "<img src='$imgUrl' style='height: 30px; width: auto; display: block; margin-bottom: 5px;'>";

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

    // --- FUNGSI PENCARI ID KOTA (PENTING!) ---
    // Fungsi ini mencari ID Kota berdasarkan Namanya
    private function getCityId($cityName, $apiKey)
    {
        // Kita simpan di Cache biar gak nembak API terus (Biar cepet)
        return Cache::remember('city_id_' . Str::slug($cityName), 60 * 24, function () use ($cityName, $apiKey) {
            
            try {
                // Ambil semua daftar kota dari RajaOngkir
                $response = Http::withoutVerifying()->withHeaders([
                    'key' => $apiKey
                ])->get('https://rajaongkir.komerce.id/api/v1/destination/domestic-destination', [
                    'search' => $cityName // Filter langsung dari API biar ringan
                ]);

                $body = $response->json();

                if (isset($body['data']) && count($body['data']) > 0) {
                    // Ambil ID dari hasil pencarian pertama
                    // Pastikan labelnya cocok dengan inputan Bagisto
                    return $body['data'][0]['id']; 
                }

            } catch (\Exception $e) {
                return null;
            }

            return null;
        });
    }
}
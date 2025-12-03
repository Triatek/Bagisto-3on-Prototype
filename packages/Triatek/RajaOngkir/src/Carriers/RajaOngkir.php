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
    
    // Wajib untuk PHP 8.2
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

        // 1. DAFTAR KURIR YANG DIINGINKAN SAJA
        // Hapus 'pos' dan 'tiki' dari sini
        $listKurir = ['jne', 'jnt', 'sicepat', 'ninja'];

        $totalWeight = 0;
        foreach ($cart->items as $item) {
            $totalWeight += $item->total_weight > 0 ? $item->total_weight : 1000; 
        }

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
                    'weight'          => $totalWeight * 1000, 
                    'courier'         => $kurir, 
                ]);

                $body = $response->json();

                if (isset($body['data']) && !empty($body['data'])) {
                    foreach ($body['data'] as $cost) {
                        
                        $object = new CartShippingRate;
                        $object->carrier = 'rajaongkir';
                        
// --- MEMBUAT LOGO ---
                        $imgUrl = asset('images/' . $kurir . '.png');
                        
                        // PERUBAHAN DISINI:
                        // 1. Pakai 'height: 40px' (biar tingginya sama rata semua)
                        // 2. Hapus 'width' (biar proporsional, gak gepeng)
                        // 3. Tambah 'margin-bottom' biar gak nempel sama harga
                        $logoHtml = "<img src='$imgUrl' style='height: 40px; width: auto; object-fit: contain; display: block; margin-bottom: 8px;'>";

                        // Kita Hapus teks nama kurir, biar cuma LOGO aja yang tampil (lebih bersih)
                        // Atau kalau mau tetap ada teks, taruh di bawahnya
                        $object->carrier_title = $logoHtml;
                        
                        $object->method = 'rajaongkir_' . $kurir . '_' . $cost['service'];
                        
                        // Judul Service (misal: REGULER)
                        $object->method_title = $cost['service']; 
                        
                        // Deskripsi
                        $object->method_description = $cost['description'] . ' (' . $cost['etd'] . ')';
                        
                        $object->price = $cost['cost'];
                        $object->base_price = $cost['cost'];
                        
                        $this->rates[] = $object;
                    }
                }

            } catch (\Exception $e) {
                // Lanjut ke kurir berikutnya
                continue;
            }
        }

        return $this->rates;
    }

    // Fungsi getCityId biarkan saja di bawah sini (untuk pengembangan nanti)
    private function getCityId($cityName, $apiKey)
    {
        // ... (kode sama seperti sebelumnya) ...
        return null;
    }
}
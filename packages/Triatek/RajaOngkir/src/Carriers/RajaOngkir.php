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

        if (!$shippingAddress || (!$shippingAddress->postcode && !$shippingAddress->city)) {
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
        
        // 2. CARI ID TUJUAN (BERDASARKAN KODE POS, CADANGAN: NAMA KOTA)
        $destinationId = $this->getDestinationId($shippingAddress->postcode, $shippingAddress->city, $apiKey);

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

                        // Blokir layanan kargo/trucking & khusus (barang berbahaya, berharga, dokumen)
                        // berdasarkan kata kunci di nama layanan maupun deskripsinya
                        $blockedKeywords = [
                            'CARGO', 'KARGO', 'TRUCK', 'DANGEROUS', 'VALUABLE', 'DOCUMENT', 'DOKUMEN'
                        ];

                        $serviceText = $serviceCode . ' ' . strtoupper($cost['description']);

                        if (Str::contains($serviceText, $blockedKeywords)) {
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

    // --- FUNGSI PENCARI ID TUJUAN ---
    // ID Komerce berada di tingkat kelurahan, jadi pencarian utama memakai kode pos.
    // Pencarian nama kota hanya cadangan, karena "Kota Bandung" bisa cocok ke "Kota Agung" (Lampung).
    private function getDestinationId($postcode, $cityName, $apiKey)
    {
        $postcode = preg_replace('/\D/', '', (string) $postcode);

        if (strlen($postcode) === 5) {
            $destinationId = Cache::remember('rajaongkir_dest_postcode_' . $postcode, 60 * 24, function () use ($postcode, $apiKey) {
                foreach ($this->searchDestination($postcode, $apiKey) as $destination) {
                    if ((string) $destination['zip_code'] === $postcode) {
                        return $destination['id'];
                    }
                }

                return null;
            });

            if ($destinationId) {
                return $destinationId;
            }
        }

        if (! $cityName) {
            return null;
        }

        // Buang awalan "Kota" / "Kabupaten" / "Kab." agar tidak cocok ke nama daerah lain
        $cityName = trim(preg_replace('/^(kota|kabupaten|kab\.?)\s+/i', '', trim($cityName)));

        return Cache::remember('rajaongkir_dest_city_' . Str::slug($cityName), 60 * 24, function () use ($cityName, $apiKey) {
            foreach ($this->searchDestination($cityName, $apiKey) as $destination) {
                if (strcasecmp($destination['city_name'], $cityName) === 0) {
                    return $destination['id'];
                }
            }

            return null;
        });
    }

    private function searchDestination($keyword, $apiKey)
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'key' => $apiKey
            ])->get('https://rajaongkir.komerce.id/api/v1/destination/domestic-destination', [
                'search' => $keyword,
                'limit'  => 50,
            ]);

            return $response->json('data') ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
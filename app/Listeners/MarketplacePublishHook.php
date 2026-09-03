<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ===================================================================
 * MarketplacePublishHook — pemicu publish produk real-time
 * ===================================================================
 *
 * Memberi tahu sidecar stock-sync (Node, repo terpisah) begitu admin menyimpan
 * produk, supaya produk yang di-opt-in langsung diunggah ke Shopee/TikTok tanpa
 * menunggu siklus cron 15 menit di sidecar.
 *
 * CATATAN EVENT: hook ini dipasang pada 'catalog.product.update.after', BUKAN
 * 'catalog.product.create.after'. Di admin Bagisto, "create" hanya menyimpan
 * sku + type + attribute_family; nama, deskripsi, harga, gambar, dan varian
 * baru terisi saat form edit disimpan (yang memicu update.after). Memasang di
 * create.after membuat sidecar selalu menolak produk karena data belum lengkap.
 *
 * Event ini juga dipicu alur lain (invoice, shipment, update inventori), jadi
 * filter di bawah sengaja dibuat ketat agar tidak menembak sidecar tiap order.
 *
 * Kegagalan sengaja ditelan: cron di sidecar tetap menjadi jaring pengaman,
 * jadi hook yang gagal hanya menunda publish, tidak menggagalkannya.
 */
class MarketplacePublishHook
{
    /**
     * Bagisto men-dispatch event ini dengan model produk sebagai payload
     * (Event::dispatch('catalog.product.update.after', $product)), sehingga
     * listener menerima Product langsung — bukan objek pembungkus ber-property
     * ->product.
     */
    public function handle($product): void
    {
        if (! config('marketplace_hook.enabled')) {
            return;
        }

        if (! $product || ! isset($product->id)) {
            return;
        }

        // Hanya produk induk (configurable) yang bisa dipublish. Alur order
        // memicu event ini dengan varian simple → tersaring di sini.
        if ($product->type !== 'configurable') {
            return;
        }

        // Ambil instance segar: $product yang dibawa event bisa memuat
        // attribute_values versi sebelum update, sehingga flag yang baru saja
        // dicentang admin belum terbaca.
        $product = $product->fresh();

        if (! $product) {
            return;
        }

        $channels = [];

        if ($this->isOptedIn($product, 'publish_to_shopee', 'shopee_product_id')) {
            $channels[] = 'shopee';
        }

        // Kode atributnya memang tidak seragam dengan Shopee: sidecar menyaring
        // kandidat TikTok lewat 'push_to_tiktokshop' (getProductsToPublishTikTok),
        // jadi flag di sini harus yang sama. Memakai 'publish_to_tiktok' membuat
        // getter selalu null → hook tidak pernah menyertakan channel tiktok.
        if ($this->isOptedIn($product, 'push_to_tiktokshop', 'tiktok_product_id')) {
            $channels[] = 'tiktok';
        }

        if (empty($channels)) {
            return;
        }

        $payload = [
            'product_id' => $product->id,
            'sku'        => $product->sku,
            'channels'   => $channels,
            'ts'         => now()->toIso8601String(),
        ];

        // Kirim setelah response dikirim ke browser agar admin tidak menunggu
        // round-trip HTTP ke sidecar.
        app()->terminating(fn () => $this->notify($payload));
    }

    /**
     * Produk di-opt-in ke sebuah channel bila flag-nya dicentang DAN belum
     * punya id marketplace (belum pernah terpublish). Sidecar memvalidasi ulang
     * syarat ini lewat query database; pengecekan di sini hanya untuk menghindari
     * HTTP call yang pasti tidak menghasilkan apa-apa.
     */
    private function isOptedIn($product, string $flagCode, string $idCode): bool
    {
        // Atribut kustom diakses lewat magic getter Product::getAttribute().
        // Bila atributnya belum dibuat admin / tidak ada di attribute family,
        // nilainya null → dianggap tidak opt-in.
        $flag = $product->{$flagCode} ?? null;

        if (! filter_var($flag, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $marketplaceId = $product->{$idCode} ?? null;

        return $marketplaceId === null || $marketplaceId === '';
    }

    /**
     * POST ke sidecar dengan signature HMAC-SHA256 atas raw body.
     */
    private function notify(array $payload): void
    {
        $url    = rtrim((string) config('marketplace_hook.url'), '/');
        $secret = (string) config('marketplace_hook.secret');

        if ($url === '' || $secret === '') {
            Log::warning('[MarketplaceHook] STOCK_SYNC_URL / STOCK_SYNC_SECRET belum diisi — hook dilewati');

            return;
        }

        // Body ditandatangani harus persis byte yang dikirim, jadi JSON di-encode
        // sekali di sini lalu dipakai untuk signature sekaligus request body.
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $signature = hash_hmac('sha256', $body, $secret);

        try {
            $response = Http::timeout((int) config('marketplace_hook.timeout', 3))
                ->withHeaders([
                    'Content-Type'        => 'application/json',
                    'X-Bagisto-Signature' => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($url);

            if ($response->successful()) {
                Log::info('[MarketplaceHook] Sidecar diberi tahu', [
                    'product_id' => $payload['product_id'],
                    'channels'   => $payload['channels'],
                ]);
            } else {
                Log::warning('[MarketplaceHook] Sidecar menolak hook', [
                    'product_id' => $payload['product_id'],
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            // Sidecar mati / tidak terjangkau. Bukan masalah fatal: cron di
            // sidecar akan mengambil produk ini pada siklus berikutnya.
            Log::warning('[MarketplaceHook] Gagal menghubungi sidecar: '.$e->getMessage(), [
                'product_id' => $payload['product_id'],
            ]);
        }
    }
}

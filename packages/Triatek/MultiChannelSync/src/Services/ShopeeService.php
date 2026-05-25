<?php

namespace Triatek\MultiChannelSync\Services;

use Laraditz\Shopee\Facades\Shopee;
use Triatek\MultiChannelSync\Models\ChannelProduct;
use Illuminate\Support\Facades\Log;

class ShopeeService
{
    // ────────────────────────────────────────────────────────────────────
    //  UPLOAD PRODUK BARU
    // ────────────────────────────────────────────────────────────────────

    public function createProduct($bagistoProduct): array
    {
        $categoryId = $this->mapCategory($bagistoProduct);
        $images     = $this->uploadImages($bagistoProduct);

        $payload = [
            'item_name'   => $bagistoProduct->name,
            'description' => strip_tags($bagistoProduct->description ?? $bagistoProduct->name),
            'category_id' => $categoryId,
            'price'       => (float) $bagistoProduct->price,
            'stock'       => (int) ($bagistoProduct->totalQuantity() ?? 0),
            'weight'      => (float) ($bagistoProduct->weight ?? 0.5),   // kg
            'item_status' => 'NORMAL',
            'image'       => ['image_id_list' => $images],
            'logistic_info' => [[
                'logistic_id'  => $this->getDefaultLogisticId(),
                'enabled'      => true,
                'is_free'      => false,
            ]],
        ];

        $response = Shopee::product()->addItem(...$payload);

        if (isset($response['item_id'])) {
            // Simpan mapping
            ChannelProduct::updateOrCreate(
                ['bagisto_product_id' => $bagistoProduct->id, 'channel' => 'shopee'],
                [
                    'channel_product_id' => $response['item_id'],
                    'status'             => 'synced',
                    'last_synced_at'     => now(),
                ]
            );

            Log::info("[Shopee] Produk berhasil dibuat", [
                'bagisto_id' => $bagistoProduct->id,
                'shopee_id'  => $response['item_id'],
            ]);

            return ['success' => true, 'item_id' => $response['item_id']];
        }

        throw new \Exception("Shopee createProduct gagal: " . json_encode($response));
    }

    // ────────────────────────────────────────────────────────────────────
    //  UPDATE PRODUK (nama, deskripsi, harga)
    // ────────────────────────────────────────────────────────────────────

    public function updateProduct($bagistoProduct): array
    {
        $mapping = ChannelProduct::findMapping($bagistoProduct->id, 'shopee');

        if (!$mapping) {
            // Produk belum ada di Shopee — buat baru
            return $this->createProduct($bagistoProduct);
        }

        $response = Shopee::product()->updateItem(
            item_id:     (int) $mapping->channel_product_id,
            item_name:   $bagistoProduct->name,
            description: strip_tags($bagistoProduct->description ?? $bagistoProduct->name),
            price:       (float) $bagistoProduct->price,
        );

        $mapping->markSynced();

        Log::info("[Shopee] Produk diupdate", ['shopee_id' => $mapping->channel_product_id]);

        return ['success' => true, 'item_id' => $mapping->channel_product_id];
    }

    // ────────────────────────────────────────────────────────────────────
    //  SINKRONISASI STOK
    // ────────────────────────────────────────────────────────────────────

    public function syncStock($bagistoProduct, int $newQty): bool
    {
        $mapping = ChannelProduct::findMapping($bagistoProduct->id, 'shopee');

        if (!$mapping || $mapping->status !== 'synced') {
            Log::warning("[Shopee] Sinkronisasi stok dilewati — produk belum tersinkronisasi", [
                'bagisto_id' => $bagistoProduct->id,
            ]);
            return false;
        }

        $response = Shopee::product()->updateStock(
            item_id:    (int) $mapping->channel_product_id,
            stock_list: [['model_id' => 0, 'seller_stock' => [['stock' => $newQty]]]],
        );

        // Catat log perubahan stok
        \DB::table('channel_stock_logs')->insert([
            'bagisto_product_id' => $bagistoProduct->id,
            'channel'            => 'shopee',
            'qty_before'         => 0,     // bisa diisi dari data sebelumnya
            'qty_after'          => $newQty,
            'result'             => isset($response['failure_list']) && empty($response['failure_list']) ? 'success' : 'failed',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        Log::info("[Shopee] Stok diperbarui", [
            'shopee_id' => $mapping->channel_product_id,
            'qty'       => $newQty,
        ]);

        return true;
    }

    // ────────────────────────────────────────────────────────────────────
    //  HELPER METHODS
    // ────────────────────────────────────────────────────────────────────

    private function mapCategory($product): int
    {
        // Ambil category_id pertama dari produk Bagisto
        $bagistoCategoryId = $product->categories()->first()?->id ?? 1;
        $map = config('multichannel.shopee.category_map', []);

        return $map[$bagistoCategoryId] ?? array_values($map)[0] ?? 100001;
    }

    private function uploadImages($product): array
    {
        $imageIds = [];

        // Ambil semua gambar produk dari Bagisto
        foreach ($product->images as $image) {
            $imagePath = storage_path('app/public/' . $image->path);

            if (!file_exists($imagePath)) {
                continue;
            }

            $response = Shopee::mediaspace()->uploadImage(
                image: new \CURLFile($imagePath, 'image/jpeg', basename($imagePath))
            );

            if (isset($response['image_id'])) {
                $imageIds[] = $response['image_id'];
            }
        }

        return $imageIds;
    }

    private function getDefaultLogisticId(): int
    {
        // Ambil logistic_id yang tersedia dari toko Anda
        // Jalankan sekali: Shopee::logistics()->getLogisticsChannelList()
        // lalu isi di sini atau simpan di config
        return config('multichannel.shopee.default_logistic_id', 80003);
    }
}

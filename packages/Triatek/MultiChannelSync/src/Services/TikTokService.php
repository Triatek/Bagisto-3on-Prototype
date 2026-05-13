<?php

namespace Triatek\MultiChannelSync\Services;

use Laraditz\TikTok\Facades\TikTok;
use Triatek\MultiChannelSync\Models\ChannelProduct;
use Illuminate\Support\Facades\Log;

class TikTokService
{
    // ────────────────────────────────────────────────────────────────────
    //  UPLOAD PRODUK BARU
    // ────────────────────────────────────────────────────────────────────

    public function createProduct($bagistoProduct): array
    {
        $categoryId = $this->mapCategory($bagistoProduct);
        $images     = $this->uploadImages($bagistoProduct);

        $payload = [
            'title'       => $bagistoProduct->name,
            'description' => strip_tags($bagistoProduct->description ?? $bagistoProduct->name),
            'category_id' => $categoryId,
            'brand_id'    => '0',       // '0' = no brand / unbranded
            'main_images' => $images,
            'skus' => [[
                'price' => [
                    'amount'        => number_format((float) $bagistoProduct->price, 2, '.', ''),
                    'currency'      => 'IDR',
                ],
                'stock_infos' => [[
                    'warehouse_id'     => $this->getWarehouseId(),
                    'available_stock'  => (int) ($bagistoProduct->totalQuantity() ?? 0),
                ]],
                'seller_sku'  => $bagistoProduct->sku ?? 'SKU-' . $bagistoProduct->id,
            ]],
            'package_weight' => (string) (($bagistoProduct->weight ?? 0.5) * 1000), // gram
            'package_length' => '10',
            'package_width'  => '10',
            'package_height' => '10',
        ];

        $response = TikTok::product()->create(body: $payload);

        if (isset($response['data']['product_id'])) {
            $productId = $response['data']['product_id'];
            $skuId     = $response['data']['skus'][0]['id'] ?? null;

            // Simpan mapping
            ChannelProduct::updateOrCreate(
                ['bagisto_product_id' => $bagistoProduct->id, 'channel' => 'tiktok'],
                [
                    'channel_product_id' => $productId,
                    'channel_sku_id'     => $skuId,
                    'status'             => 'synced',
                    'last_synced_at'     => now(),
                ]
            );

            Log::info("[TikTok] Produk berhasil dibuat", [
                'bagisto_id' => $bagistoProduct->id,
                'tiktok_id'  => $productId,
            ]);

            return ['success' => true, 'product_id' => $productId];
        }

        throw new \Exception("TikTok createProduct gagal: " . json_encode($response));
    }

    // ────────────────────────────────────────────────────────────────────
    //  UPDATE PRODUK (nama, deskripsi, harga)
    // ────────────────────────────────────────────────────────────────────

    public function updateProduct($bagistoProduct): array
    {
        $mapping = ChannelProduct::findMapping($bagistoProduct->id, 'tiktok');

        if (!$mapping) {
            return $this->createProduct($bagistoProduct);
        }

        $response = TikTok::product()->editProduct(
            params: ['product_id' => $mapping->channel_product_id],
            body: [
                'title'       => $bagistoProduct->name,
                'description' => strip_tags($bagistoProduct->description ?? $bagistoProduct->name),
                'skus' => [[
                    'id'    => $mapping->channel_sku_id,
                    'price' => [
                        'amount'   => number_format((float) $bagistoProduct->price, 2, '.', ''),
                        'currency' => 'IDR',
                    ],
                ]],
            ]
        );

        $mapping->markSynced();

        Log::info("[TikTok] Produk diupdate", ['tiktok_id' => $mapping->channel_product_id]);

        return ['success' => true, 'product_id' => $mapping->channel_product_id];
    }

    // ────────────────────────────────────────────────────────────────────
    //  SINKRONISASI STOK
    // ────────────────────────────────────────────────────────────────────

    public function syncStock($bagistoProduct, int $newQty): bool
    {
        $mapping = ChannelProduct::findMapping($bagistoProduct->id, 'tiktok');

        if (!$mapping || $mapping->status !== 'synced') {
            Log::warning("[TikTok] Sinkronisasi stok dilewati — produk belum tersinkronisasi", [
                'bagisto_id' => $bagistoProduct->id,
            ]);
            return false;
        }

        $response = TikTok::product()->updateInventory(
            params: ['product_id' => $mapping->channel_product_id],
            body: [
                'skus' => [[
                    'id'          => $mapping->channel_sku_id,
                    'inventory'   => [[
                        'warehouse_id' => $this->getWarehouseId(),
                        'quantity'     => $newQty,
                    ]],
                ]],
            ]
        );

        // Catat log perubahan stok
        \DB::table('channel_stock_logs')->insert([
            'bagisto_product_id' => $bagistoProduct->id,
            'channel'            => 'tiktok',
            'qty_before'         => 0,
            'qty_after'          => $newQty,
            'result'             => isset($response['data']) ? 'success' : 'failed',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        Log::info("[TikTok] Stok diperbarui", [
            'tiktok_id' => $mapping->channel_product_id,
            'qty'       => $newQty,
        ]);

        return true;
    }

    // ────────────────────────────────────────────────────────────────────
    //  HELPER METHODS
    // ────────────────────────────────────────────────────────────────────

    private function mapCategory($product): string
    {
        $bagistoCategoryId = $product->categories()->first()?->id ?? 1;
        $map = config('multichannel.tiktok.category_map', []);

        return (string) ($map[$bagistoCategoryId] ?? array_values($map)[0] ?? '600001');
    }

    private function uploadImages($product): array
    {
        $images = [];

        foreach ($product->images as $image) {
            $imagePath = storage_path('app/public/' . $image->path);

            if (!file_exists($imagePath)) {
                continue;
            }

            // Encode ke base64 untuk TikTok API
            $base64 = base64_encode(file_get_contents($imagePath));

            $response = TikTok::product()->uploadProductImage(
                body: ['data' => $base64]
            );

            if (isset($response['data']['uri'])) {
                $images[] = ['uri' => $response['data']['uri']];
            }
        }

        return $images;
    }

    private function getWarehouseId(): string
    {
        // Dapatkan warehouse_id dari TikTok::logistics()->getWarehouseList()
        // lalu simpan di config atau env
        return config('multichannel.tiktok.warehouse_id', 'default_warehouse_id');
    }
}

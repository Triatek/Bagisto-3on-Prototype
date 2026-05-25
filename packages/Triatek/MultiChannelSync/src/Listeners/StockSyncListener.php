<?php

namespace Triatek\MultiChannelSync\Listeners;

use Triatek\MultiChannelSync\Jobs\SyncStockToShopeeJob;
use Triatek\MultiChannelSync\Jobs\SyncStockToTikTokJob;
use Triatek\MultiChannelSync\Models\ChannelProduct;
use Illuminate\Support\Facades\Log;

class StockSyncListener
{
    public function handle($event): void
    {
        $product = $event->product;

        // Ambil stok terbaru dari Bagisto
        $newQty = (int) ($product->totalQuantity() ?? 0);

        // Hanya proses jika produk sudah pernah disinkronisasi ke marketplace
        $hasShopeeMapping = ChannelProduct::findMapping($product->id, 'shopee')?->status === 'synced';
        $hasTikTokMapping = ChannelProduct::findMapping($product->id, 'tiktok')?->status === 'synced';

        if (!$hasShopeeMapping && !$hasTikTokMapping) {
            return; // Produk belum pernah di-sync, skip
        }

        Log::info("[MultiChannel] Perubahan stok terdeteksi, sinkronisasi ke marketplace", [
            'product_id' => $product->id,
            'new_qty'    => $newQty,
        ]);

        if ($hasShopeeMapping && config('multichannel.channels.shopee', true)) {
            SyncStockToShopeeJob::dispatch($product->id, $newQty)
                ->onQueue(config('multichannel.queue.name', 'marketplace-sync'));
        }

        if ($hasTikTokMapping && config('multichannel.channels.tiktok', true)) {
            SyncStockToTikTokJob::dispatch($product->id, $newQty)
                ->onQueue(config('multichannel.queue.name', 'marketplace-sync'));
        }
    }
}

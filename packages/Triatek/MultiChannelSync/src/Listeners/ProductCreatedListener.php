<?php

namespace Triatek\MultiChannelSync\Listeners;

use Triatek\MultiChannelSync\Jobs\SyncProductToShopeeJob;
use Triatek\MultiChannelSync\Jobs\SyncProductToTikTokJob;
use Illuminate\Support\Facades\Log;

class ProductCreatedListener
{
    /**
     * Dipanggil otomatis oleh Bagisto saat event 'catalog.product.create.after'
     * $event->product berisi objek produk yang baru dibuat
     */
    public function handle($event): void
    {
        $product = $event->product;

        Log::info("[MultiChannel] Produk baru terdeteksi, memulai sinkronisasi", [
            'product_id'   => $product->id,
            'product_name' => $product->name,
        ]);

        // Dispatch job ke queue — tidak memblokir proses simpan di admin
        if (config('multichannel.channels.shopee', true)) {
            SyncProductToShopeeJob::dispatch($product->id)
                ->onQueue(config('multichannel.queue.name', 'marketplace-sync'));
        }

        if (config('multichannel.channels.tiktok', true)) {
            SyncProductToTikTokJob::dispatch($product->id)
                ->onQueue(config('multichannel.queue.name', 'marketplace-sync'));
        }
    }
}

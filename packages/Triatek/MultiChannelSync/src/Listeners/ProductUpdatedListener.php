<?php

namespace Triatek\MultiChannelSync\Listeners;

use Triatek\MultiChannelSync\Jobs\UpdateProductOnShopeeJob;
use Triatek\MultiChannelSync\Jobs\UpdateProductOnTikTokJob;
use Illuminate\Support\Facades\Log;

class ProductUpdatedListener
{
    public function handle($event): void
    {
        $product = $event->product;

        Log::info("[MultiChannel] Update produk terdeteksi", [
            'product_id' => $product->id,
        ]);

        if (config('multichannel.channels.shopee', true)) {
            UpdateProductOnShopeeJob::dispatch($product->id)
                ->onQueue(config('multichannel.queue.name', 'marketplace-sync'));
        }

        if (config('multichannel.channels.tiktok', true)) {
            UpdateProductOnTikTokJob::dispatch($product->id)
                ->onQueue(config('multichannel.queue.name', 'marketplace-sync'));
        }
    }
}

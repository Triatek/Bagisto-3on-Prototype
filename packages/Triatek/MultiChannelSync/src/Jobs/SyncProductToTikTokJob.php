<?php

namespace Triatek\MultiChannelSync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Triatek\MultiChannelSync\Models\ChannelProduct;
use Triatek\MultiChannelSync\Services\TikTokService;
use Webkul\Product\Models\Product;

class SyncProductToTikTokJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $bagistoProductId) {}

    public function handle(TikTokService $tiktok): void
    {
        $product = Product::with(['images', 'categories'])->find($this->bagistoProductId);

        if (! $product) {
            return;
        }

        try {
            $tiktok->createProduct($product);
        } catch (\Throwable $e) {
            Log::error('[TikTok] Gagal upload produk: '.$e->getMessage(), [
                'product_id' => $this->bagistoProductId,
            ]);

            ChannelProduct::updateOrCreate(
                ['bagisto_product_id' => $this->bagistoProductId, 'channel' => 'tiktok'],
                ['status' => 'failed', 'last_error' => $e->getMessage()]
            );

            $this->fail($e);
        }
    }
}

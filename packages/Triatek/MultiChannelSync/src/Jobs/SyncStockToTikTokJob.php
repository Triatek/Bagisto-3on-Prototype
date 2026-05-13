<?php

namespace Triatek\MultiChannelSync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Triatek\MultiChannelSync\Services\TikTokService;
use Webkul\Product\Models\Product;

class SyncStockToTikTokJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(
        public int $bagistoProductId,
        public int $newQty
    ) {}

    public function handle(TikTokService $tiktok): void
    {
        $product = Product::find($this->bagistoProductId);

        if (! $product) {
            return;
        }

        try {
            $tiktok->syncStock($product, $this->newQty);
        } catch (\Throwable $e) {
            Log::error('[TikTok] Gagal sync stok: '.$e->getMessage(), [
                'product_id' => $this->bagistoProductId,
                'qty' => $this->newQty,
            ]);

            $this->fail($e);
        }
    }
}

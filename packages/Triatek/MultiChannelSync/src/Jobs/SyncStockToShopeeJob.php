<?php

namespace Triatek\MultiChannelSync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Triatek\MultiChannelSync\Services\ShopeeService;
use Webkul\Product\Models\Product;

class SyncStockToShopeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(
        public int $bagistoProductId,
        public int $newQty
    ) {}

    public function handle(ShopeeService $shopee): void
    {
        $product = Product::find($this->bagistoProductId);

        if (! $product) {
            return;
        }

        try {
            $shopee->syncStock($product, $this->newQty);
        } catch (\Throwable $e) {
            Log::error('[Shopee] Gagal sync stok: '.$e->getMessage(), [
                'product_id' => $this->bagistoProductId,
                'qty' => $this->newQty,
            ]);

            $this->fail($e);
        }
    }
}

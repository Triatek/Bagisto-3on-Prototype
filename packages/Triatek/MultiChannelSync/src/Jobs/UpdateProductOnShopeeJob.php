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

class UpdateProductOnShopeeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $bagistoProductId) {}

    public function handle(ShopeeService $shopee): void
    {
        $product = Product::with(['images', 'categories'])->find($this->bagistoProductId);

        if (! $product) {
            return;
        }

        try {
            $shopee->updateProduct($product);
        } catch (\Throwable $e) {
            Log::error('[Shopee] Gagal update produk: '.$e->getMessage());
            $this->fail($e);
        }
    }
}

<?php

// ════════════════════════════════════════════════════════════════════════
//  Event: MarketplaceOrderReceived
//  Dipanggil saat webhook order baru diterima dari Shopee / TikTok Shop
// ════════════════════════════════════════════════════════════════════════

namespace Triatek\MultiChannelSync\Events;

use Illuminate\Foundation\Events\Dispatchable;

class MarketplaceOrderReceived
{
    use Dispatchable;

    public function __construct(
        public string $channel,    // 'shopee' | 'tiktok'
        public string $orderId,    // order_sn / order_id dari marketplace
        public array  $payload,    // raw payload dari webhook
    ) {}
}

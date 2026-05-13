<?php

namespace Triatek\MultiChannelSync\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Triatek\MultiChannelSync\Events\MarketplaceOrderReceived;
use Triatek\MultiChannelSync\Models\ChannelProduct;

class WebhookController extends Controller
{
    // ────────────────────────────────────────────────────────────────────
    //  WEBHOOK SHOPEE
    //  URL: POST /multichannel/webhook/shopee
    //  Set di Shopee Open Platform → App Detail → Webhook URL
    // ────────────────────────────────────────────────────────────────────

    public function shopee(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->all();

        Log::info("[Shopee Webhook] Diterima", ['code' => $payload['code'] ?? null]);

        // Verifikasi signature Shopee
        if (!$this->verifyShopeeSignature($request)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $code = $payload['code'] ?? null;

        match ($code) {
            3  => $this->handleShopeeOrderStatus($payload),  // Order status update
            4  => $this->handleShopeeStockUpdate($payload),  // Stock update dari Shopee
            15 => $this->handleShopeeOrderTrack($payload),   // Tracking update
            default => Log::info("[Shopee Webhook] Event tidak ditangani", ['code' => $code]),
        };

        return response()->json(['message' => 'OK']);
    }

    // ────────────────────────────────────────────────────────────────────
    //  WEBHOOK TIKTOK SHOP
    //  URL: POST /multichannel/webhook/tiktok
    //  Set di TikTok Developer Portal → Event Subscriptions
    // ────────────────────────────────────────────────────────────────────

    public function tiktok(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->all();

        Log::info("[TikTok Webhook] Diterima", ['type' => $payload['type'] ?? null]);

        // Verifikasi signature TikTok
        if (!$this->verifyTikTokSignature($request)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $type = $payload['type'] ?? null;

        match ($type) {
            'ORDER_STATUS_CHANGE'      => $this->handleTikTokOrderStatus($payload),
            'PRODUCT_STATUS_CHANGE'    => $this->handleTikTokProductStatus($payload),
            'RETURN_STATUS_CHANGE'     => $this->handleTikTokReturn($payload),
            default => Log::info("[TikTok Webhook] Event tidak ditangani", ['type' => $type]),
        };

        return response()->json(['message' => 'OK']);
    }

    // ────────────────────────────────────────────────────────────────────
    //  HANDLER: Order baru dari Shopee
    // ────────────────────────────────────────────────────────────────────

    private function handleShopeeOrderStatus(array $payload): void
    {
        $orderSn = $payload['data']['ordersn'] ?? null;
        $status  = $payload['data']['status'] ?? null;

        if (!$orderSn) return;

        // Simpan order ke tabel channel_orders
        $channelOrder = \DB::table('channel_orders')->updateOrInsert(
            ['channel' => 'shopee', 'channel_order_id' => $orderSn],
            [
                'status'      => 'received',
                'raw_payload' => json_encode($payload),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );

        // Ambil detail order lengkap dari API Shopee & buat di Bagisto
        if ($status === 'READY_TO_SHIP') {
            event(new MarketplaceOrderReceived('shopee', $orderSn, $payload));
        }

        // Kurangi stok Bagisto otomatis setelah order confirmed
        if (in_array($status, ['READY_TO_SHIP', 'SHIPPED'])) {
            $this->decreaseBagistoStock($payload['data']['item_list'] ?? [], 'shopee');
        }
    }

    // ────────────────────────────────────────────────────────────────────
    //  HANDLER: Order baru dari TikTok Shop
    // ────────────────────────────────────────────────────────────────────

    private function handleTikTokOrderStatus(array $payload): void
    {
        $orderId = $payload['data']['order_id']     ?? null;
        $status  = $payload['data']['order_status'] ?? null;

        if (!$orderId) return;

        \DB::table('channel_orders')->updateOrInsert(
            ['channel' => 'tiktok', 'channel_order_id' => $orderId],
            [
                'status'      => 'received',
                'raw_payload' => json_encode($payload),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );

        if ($status === 'AWAITING_SHIPMENT') {
            event(new MarketplaceOrderReceived('tiktok', $orderId, $payload));
        }
    }

    // ────────────────────────────────────────────────────────────────────
    //  HANDLER: Update stok dari Shopee (jika Shopee yang mengurangi)
    // ────────────────────────────────────────────────────────────────────

    private function handleShopeeStockUpdate(array $payload): void
    {
        // Saat stok di Shopee berubah (misal dari flash sale),
        // sinkronisasi balik ke Bagisto agar tidak oversell
        Log::info("[Shopee Webhook] Update stok diterima", $payload);
    }

    private function handleTikTokProductStatus(array $payload): void
    {
        Log::info("[TikTok Webhook] Product status change", $payload);
    }

    private function handleTikTokReturn(array $payload): void
    {
        Log::info("[TikTok Webhook] Return request diterima", $payload);
    }

    private function handleShopeeOrderTrack(array $payload): void
    {
        Log::info("[Shopee Webhook] Tracking update", $payload);
    }

    // ────────────────────────────────────────────────────────────────────
    //  Kurangi stok Bagisto saat ada order dari marketplace
    // ────────────────────────────────────────────────────────────────────

    private function decreaseBagistoStock(array $items, string $channel): void
    {
        foreach ($items as $item) {
            $shopeeItemId = $item['item_id'] ?? null;
            if (!$shopeeItemId) continue;

            // Cari mapping produk Bagisto dari item_id Shopee
            $mapping = ChannelProduct::where('channel', $channel)
                ->where('channel_product_id', $shopeeItemId)
                ->first();

            if (!$mapping) continue;

            // Kurangi stok di Bagisto
            $product = \Webkul\Product\Models\Product::find($mapping->bagisto_product_id);
            if ($product) {
                // Bagisto menyediakan ProductInventory untuk update stok
                $inventory = $product->inventories()->first();
                if ($inventory) {
                    $newQty = max(0, $inventory->qty - ($item['model_quantity_purchased'] ?? 1));
                    $inventory->update(['qty' => $newQty]);

                    Log::info("[MultiChannel] Stok Bagisto dikurangi karena order {$channel}", [
                        'product_id' => $product->id,
                        'new_qty'    => $newQty,
                    ]);
                }
            }
        }
    }

    // ────────────────────────────────────────────────────────────────────
    //  VERIFIKASI SIGNATURE
    // ────────────────────────────────────────────────────────────────────

    private function verifyShopeeSignature(Request $request): bool
    {
        $authorization = $request->header('Authorization');
        if (!$authorization) return false;

        $partnerKey  = config('multichannel.shopee.partner_key');
        $rawBody     = $request->getContent();
        $expected    = hash_hmac('sha256', $rawBody, $partnerKey);

        return hash_equals($expected, $authorization);
    }

    private function verifyTikTokSignature(Request $request): bool
    {
        $signature = $request->header('x-tts-signature');
        if (!$signature) return false;

        $appSecret = config('multichannel.tiktok.app_secret');
        $rawBody   = $request->getContent();
        $expected  = base64_encode(hash_hmac('sha256', $rawBody, $appSecret, true));

        return hash_equals($expected, $signature);
    }
}

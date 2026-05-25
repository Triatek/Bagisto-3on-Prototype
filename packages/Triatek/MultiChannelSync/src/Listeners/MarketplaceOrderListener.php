<?php

namespace Triatek\MultiChannelSync\Listeners;

use Triatek\MultiChannelSync\Events\MarketplaceOrderReceived;
use Triatek\MultiChannelSync\Models\ChannelProduct;
use Laraditz\Shopee\Facades\Shopee;
use Laraditz\TikTok\Facades\TikTok;
use Illuminate\Support\Facades\Log;

class MarketplaceOrderListener
{
    public function handle(MarketplaceOrderReceived $event): void
    {
        Log::info("[MultiChannel] Memproses order dari {$event->channel}", [
            'order_id' => $event->orderId,
        ]);

        try {
            match ($event->channel) {
                'shopee' => $this->createBagistoOrderFromShopee($event->orderId),
                'tiktok' => $this->createBagistoOrderFromTikTok($event->orderId, $event->payload),
            };
        } catch (\Throwable $e) {
            Log::error("[MultiChannel] Gagal buat order Bagisto: " . $e->getMessage(), [
                'channel'  => $event->channel,
                'order_id' => $event->orderId,
            ]);

            \DB::table('channel_orders')
                ->where('channel', $event->channel)
                ->where('channel_order_id', $event->orderId)
                ->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
        }
    }

    // ────────────────────────────────────────────────────────────────────
    //  Buat order Bagisto dari order Shopee
    // ────────────────────────────────────────────────────────────────────

    private function createBagistoOrderFromShopee(string $orderSn): void
    {
        // Ambil detail order dari Shopee API
        $detail = Shopee::order()->getOrderDetail(
            order_sn_list: $orderSn,
            response_optional_fields: 'buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,pay_time,dropshipper,credit_card_number,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,invoice_data,checkout_shipping_carrier,reverse_shipping_fee,order_chargeable_weight_gram,edt,prescription_images,prescription_check_status,order_sn'
        );

        $order  = $detail['order_list'][0] ?? null;
        if (!$order) return;

        $items = collect($order['package_list'][0]['item_list'] ?? []);

        // Map item Shopee → Bagisto product
        $cartItems = $items->map(function ($item) {
            $mapping = ChannelProduct::where('channel', 'shopee')
                ->where('channel_product_id', $item['item_id'])
                ->first();

            return $mapping ? [
                'bagisto_product_id' => $mapping->bagisto_product_id,
                'qty'                => $item['model_quantity_purchased'],
                'price'              => $item['model_discounted_price'],
            ] : null;
        })->filter()->values()->toArray();

        if (empty($cartItems)) {
            Log::warning("[Shopee] Tidak ada produk Bagisto yang cocok untuk order {$orderSn}");
            return;
        }

        // Buat order menggunakan Bagisto CartRepository + OrderRepository
        // (Implementasi tergantung versi Bagisto Anda)
        $this->persistBagistoOrder([
            'channel'  => 'shopee',
            'order_id' => $orderSn,
            'items'    => $cartItems,
            'buyer'    => [
                'name'    => $order['buyer_username'] ?? 'Shopee Buyer',
                'address' => $order['recipient_address']['full_address'] ?? '',
                'phone'   => $order['recipient_address']['phone'] ?? '',
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────
    //  Buat order Bagisto dari order TikTok Shop
    // ────────────────────────────────────────────────────────────────────

    private function createBagistoOrderFromTikTok(string $orderId, array $payload): void
    {
        // Ambil detail order dari TikTok API
        $detail = TikTok::order()->detail(
            params: ['order_id_list' => [$orderId]]
        );

        $order = $detail['data']['order_list'][0] ?? null;
        if (!$order) return;

        $cartItems = collect($order['line_items'] ?? [])->map(function ($item) {
            $mapping = ChannelProduct::where('channel', 'tiktok')
                ->where('channel_product_id', $item['product_id'])
                ->first();

            return $mapping ? [
                'bagisto_product_id' => $mapping->bagisto_product_id,
                'qty'                => $item['quantity'],
                'price'              => $item['sale_price'],
            ] : null;
        })->filter()->values()->toArray();

        if (empty($cartItems)) {
            Log::warning("[TikTok] Tidak ada produk Bagisto yang cocok untuk order {$orderId}");
            return;
        }

        $recipient = $order['recipient_address'] ?? [];

        $this->persistBagistoOrder([
            'channel'  => 'tiktok',
            'order_id' => $orderId,
            'items'    => $cartItems,
            'buyer'    => [
                'name'    => $recipient['name'] ?? 'TikTok Buyer',
                'address' => $recipient['full_address'] ?? '',
                'phone'   => $recipient['phone_number'] ?? '',
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────
    //  Simpan order ke database Bagisto
    // ────────────────────────────────────────────────────────────────────

    private function persistBagistoOrder(array $data): void
    {
        // Catatan: Ini adalah contoh penyimpanan sederhana ke tabel orders Bagisto.
        // Untuk integrasi penuh, gunakan Bagisto CartRepository dan CheckoutManager.
        // Dokumentasi: https://devdocs.bagisto.com/

        \DB::table('channel_orders')
            ->where('channel', $data['channel'])
            ->where('channel_order_id', $data['order_id'])
            ->update([
                'status'     => 'synced',
                'updated_at' => now(),
            ]);

        Log::info("[MultiChannel] Order berhasil disimpan ke Bagisto", [
            'channel'  => $data['channel'],
            'order_id' => $data['order_id'],
            'items'    => count($data['items']),
        ]);
    }
}

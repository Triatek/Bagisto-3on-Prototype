<?php

/*
|--------------------------------------------------------------------------
| Marketplace Publish Hook
|--------------------------------------------------------------------------
|
| Pemicu publish real-time ke sidecar stock-sync (service Node terpisah)
| saat admin menyimpan produk. Lihat App\Listeners\MarketplacePublishHook.
|
| Matikan (STOCK_SYNC_HOOK_ENABLED=false) bila ingin mengandalkan cron sidecar
| saja — produk tetap terpublish, hanya lebih lambat.
|
*/

return [
    'enabled' => env('STOCK_SYNC_HOOK_ENABLED', false),

    // Endpoint sidecar. Samakan port dengan WEBHOOK_PORT di .env sidecar.
    'url' => env('STOCK_SYNC_URL', 'http://127.0.0.1:3001/webhook/bagisto/product'),

    // Harus sama persis dengan BAGISTO_HOOK_SECRET di .env sidecar.
    'secret' => env('STOCK_SYNC_SECRET'),

    // Detik. Dijaga pendek: request ini terjadi setelah response dikirim,
    // tapi tetap menahan worker PHP sampai selesai.
    'timeout' => env('STOCK_SYNC_TIMEOUT', 3),
];

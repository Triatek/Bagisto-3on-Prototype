<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Aktifkan / nonaktifkan channel secara individual
    |--------------------------------------------------------------------------
    */
    'channels' => [
        'shopee'  => env('MULTICHANNEL_SHOPEE_ENABLED', true),
        'tiktok'  => env('MULTICHANNEL_TIKTOK_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Shopee Open Platform
    | Dapatkan dari: https://open.shopee.com/
    |--------------------------------------------------------------------------
    */
    'shopee' => [
        'sandbox_mode' => env('SHOPEE_SANDBOX_MODE', true),
        'partner_id'   => env('SHOPEE_PARTNER_ID'),
        'partner_key'  => env('SHOPEE_PARTNER_KEY'),
        'shop_id'      => env('SHOPEE_SHOP_ID'),
        // Mapping kategori Bagisto → Shopee category_id
        // Isi sesuai kategori toko Shopee Anda
        'category_map' => [
            // 'bagisto_category_id' => shopee_category_id
            1  => 100001,
            2  => 100002,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi TikTok Shop Open API
    | Dapatkan dari: https://developers.tiktok.com/
    |--------------------------------------------------------------------------
    */
    'tiktok' => [
        'app_key'    => env('TIKTOK_APP_KEY'),
        'app_secret' => env('TIKTOK_APP_SECRET'),
        'shop_id'    => env('TIKTOK_SHOP_ID'),
        // Mapping kategori Bagisto → TikTok category_id
        'category_map' => [
            // 'bagisto_category_id' => 'tiktok_category_id'
            1  => '600001',
            2  => '600002',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Queue
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('MULTICHANNEL_QUEUE_CONNECTION', 'database'),
        'name'       => env('MULTICHANNEL_QUEUE_NAME', 'marketplace-sync'),
        'retry'      => 3,   // maksimum retry jika job gagal
        'timeout'    => 60,  // detik
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'shopee_path'  => '/multichannel/webhook/shopee',
        'tiktok_path'  => '/multichannel/webhook/tiktok',
    ],

];

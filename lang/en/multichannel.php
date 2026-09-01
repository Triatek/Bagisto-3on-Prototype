<?php

return [
    'title'        => 'Multi-Channel Sales',
    'menu'         => 'Multi-Channel',
    'all_channels' => 'All Channels',
    'orders_count' => ':count orders',
    'share_title'  => 'Revenue share by channel',
    'trend_title'  => 'Daily trend',

    /**
     * Label tampilan per channel. Kuncinya harus sama dengan nilai kolom
     * `channel` di tabel `marketplace_orders` (dan 'bagisto' untuk tabel
     * `orders`) — yang berubah di sini hanya tulisan di layar, bukan datanya.
     */
    'channel' => [
        'bagisto' => 'Official Store',
        'shopee'  => 'Shopee',
        'tiktok'  => 'TikTok Shop',
    ],

    'disclaimer' => 'Quick overview only. Marketplace figures are gross buyer-paid revenue — platform commission, shipping and seller vouchers are not deducted, so these numbers will not match marketplace settlement reports. Not for financial reconciliation.',

    'period' => [
        'today'  => 'Today',
        '7days'  => 'Last 7 days',
        '30days' => 'Last 30 days',
        'apply'  => 'Apply',
    ],

    'table' => [
        'channel' => 'Channel',
        'orders'  => 'Orders',
        'revenue' => 'Revenue',
        'share'   => 'Share',
        'total'   => 'Total',
    ],

    'empty' => [
        'title' => 'No sales in this period',
        'hint'  => 'Marketplace orders are collected by the stock-sync sidecar. Check that ORDER_SYNC_ENABLED=true and that the Order API scope is active for each marketplace.',
    ],
];

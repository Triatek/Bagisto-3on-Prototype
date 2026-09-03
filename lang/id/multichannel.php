<?php

return [
    'title'        => 'Penjualan Multi-Channel',
    'menu'         => 'Multi-Channel',
    'all_channels' => 'Semua Channel',
    'orders_count' => ':count order',
    'share_title'  => 'Porsi omzet per channel',
    'trend_title'  => 'Tren harian',

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

    'disclaimer' => 'Gambaran cepat saja. Angka marketplace adalah omzet bruto yang dibayar pembeli — belum dipotong komisi platform, ongkir, maupun voucher seller, sehingga tidak akan sama dengan settlement report marketplace. Bukan untuk rekonsiliasi keuangan.',

    'period' => [
        'today'  => 'Hari ini',
        '7days'  => '7 hari terakhir',
        '30days' => '30 hari terakhir',
        'apply'  => 'Terapkan',
    ],

    'table' => [
        'channel' => 'Channel',
        'orders'  => 'Order',
        'revenue' => 'Omzet',
        'share'   => 'Porsi',
        'total'   => 'Total',
    ],

    'empty' => [
        'title' => 'Belum ada penjualan pada periode ini',
        'hint'  => 'Order marketplace dikumpulkan oleh sidecar stock-sync. Pastikan ORDER_SYNC_ENABLED=true dan scope Order API sudah aktif untuk masing-masing marketplace.',
    ],
];

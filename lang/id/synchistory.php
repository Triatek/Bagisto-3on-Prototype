<?php

return [
    'title' => 'Riwayat Sinkronisasi Stok',
    'menu'  => 'Riwayat Sinkronisasi',

    'subtitle' => 'Setiap perubahan stok beserta sebabnya',

    'disclaimer' => 'Satu baris = satu siklus sinkronisasi untuk satu SKU, bukan satu order. Beberapa order marketplace yang masuk di antara dua siklus tergabung menjadi satu baris berjumlah. Untuk daftar per-order, lihat Reporting › Multi-Channel.',

    'period' => [
        'today'  => 'Hari ini',
        '7days'  => '7 hari terakhir',
        '30days' => '30 hari terakhir',
        'apply'  => 'Terapkan',
    ],

    'filter' => [
        'sku'         => 'SKU',
        'sku_hint'    => 'cari SKU…',
        'reason'      => 'Keterangan',
        'status'      => 'Status',
        'all'         => 'Semua',
        'apply'       => 'Filter',
        'reset'       => 'Reset',
    ],

    'summary' => [
        'events'      => 'Perubahan tercatat',
        'events_hint' => ':count SKU terlibat',
        'sold'        => 'Terjual',
        'sold_hint'   => 'Shopee :shopee · TikTok :tiktok · Toko :store',
        'restocked'   => 'Ditambah admin',
        'reduced'     => 'Dikurangi admin',
        'unit'        => ':count pcs',
        'problems'    => ':count push gagal / sebagian',
        'problems_ok' => 'Semua push berhasil',
    ],

    'table' => [
        'time'    => 'Waktu',
        'sku'     => 'SKU',
        'trigger' => 'Pemicu',
        'bagisto' => 'Bagisto',
        'shopee'  => 'Shopee',
        'tiktok'  => 'TikTok',
        'final'   => 'Stok akhir',
        'reason'  => 'Keterangan',
        'none'    => '—',
    ],

    /**
     * Label kode `reasons`. Kuncinya harus sama dengan konstanta REASON di
     * sidecar (stock-sync/services/sync-history.js) — yang berubah di sini hanya
     * tulisan di layar, bukan datanya.
     */
    'reason' => [
        'purchase_shopee' => 'Pembelian Shopee',
        'purchase_tiktok' => 'Pembelian TikTok',
        'purchase_store'  => 'Pembelian toko sendiri',
        'restock'         => 'Penambahan',
        'reduction'       => 'Pengurangan',
        'calibration'     => 'Kalibrasi awal',
        'resync'          => 'Penyelarasan',
    ],

    'reason_hint' => [
        'purchase_shopee' => 'Stok Shopee turun sejak siklus terakhir — ada order masuk.',
        'purchase_tiktok' => 'Stok TikTok Shop turun sejak siklus terakhir — ada order masuk.',
        'purchase_store'  => 'Ada order di toko sendiri (Bagisto) untuk SKU ini.',
        'restock'         => 'Stok Bagisto naik tanpa ada order — admin menambah stok.',
        'reduction'       => 'Stok Bagisto turun tanpa ada order — admin mengurangi stok.',
        'calibration'     => 'SKU ini pertama kali disinkronkan; stok diselaraskan ke channel terendah.',
        'resync'          => 'Tidak ada penjualan, tetapi stok channel menyimpang dan diselaraskan ulang.',
    ],

    'status' => [
        'ok'      => 'Berhasil',
        'partial' => 'Sebagian',
        'error'   => 'Gagal',
    ],

    'trigger' => [
        'cron'    => 'Jadwal',
        'webhook' => 'Real-time',
        'startup' => 'Startup',
        'manual'  => 'Manual',
    ],

    'empty' => [
        'title'    => 'Belum ada perubahan stok pada periode ini',
        'hint'     => 'Baris hanya dibuat saat ada perubahan nyata — siklus sinkronisasi yang tidak mengubah apa pun sengaja tidak dicatat.',
        'filtered' => 'Tidak ada yang cocok dengan filter ini. Coba longgarkan filternya atau perlebar rentang tanggal.',
    ],
];

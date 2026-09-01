<?php

/**
 * Konfigurasi dashboard "Reporting › Multi-Channel".
 *
 * Dashboard ini hanya untuk INFORMASI CEPAT — bukan untuk rekonsiliasi keuangan.
 * Angka marketplace adalah omzet bruto yang dibayar pembeli, belum dipotong
 * komisi platform, ongkir, maupun voucher, sehingga tidak akan sama dengan
 * settlement report marketplace.
 */
return [
    /**
     * Status yang DIKELUARKAN dari perhitungan penjualan, per sumber.
     *
     * Sengaja exclude-list, BUKAN include-list. Marketplace punya banyak status
     * "sedang berjalan" yang semuanya tetap terhitung penjualan — order Shopee
     * nyata pertama yang masuk berstatus `TO_CONFIRM_RECEIVE`, status yang tidak
     * ada di daftar mana pun sebelumnya. Dengan include-list, order seperti itu
     * hilang diam-diam tanpa error dan omzet terlaporkan lebih kecil dari
     * kenyataan. Dengan exclude-list, status baru yang belum dikenal otomatis
     * ikut terhitung — kesalahan yang jauh lebih mudah disadari.
     *
     * Status disimpan mentah di tabel `marketplace_orders`, jadi mengubah daftar
     * di bawah langsung mengubah laporan tanpa perlu menarik ulang data dari API.
     */
    'excluded_statuses' => [
        // Tabel `orders` Bagisto — mengikuti status yang juga dikecualikan
        // laporan bawaan Bagisto. `pending` / `pending_payment` tetap dihitung.
        'bagisto' => [
            'canceled',
            'closed',
            'fraud',
        ],

        'shopee' => [
            'CANCELLED',
            'UNPAID',
            'INVOICE_PENDING',
        ],

        'tiktok' => [
            'CANCELLED',
            'UNPAID',
        ],
    ],

    /**
     * Batas jumlah hari untuk rentang custom. Menjaga grafik tren tetap terbaca
     * dan query tetap ringan.
     */
    'max_custom_days' => 366,
];

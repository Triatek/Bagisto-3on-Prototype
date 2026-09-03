<?php

/**
 * Konfigurasi halaman "Reporting › Riwayat Sinkronisasi".
 *
 * Halaman ini membaca tabel `stock_sync_history` yang diisi sidecar stock-sync.
 * Retensi datanya diatur DI SIDECAR (`SYNC_HISTORY_RETENTION_DAYS` di .env-nya,
 * default 90 hari), bukan di sini — yang menghapus baris lama adalah cron sidecar,
 * jadi menaruh angkanya di dua tempat hanya akan membuat keduanya berbeda.
 */
return [
    /**
     * Jumlah baris per halaman. Satu perubahan stok = satu baris, jadi toko yang
     * aktif bisa menghasilkan ratusan baris per hari.
     */
    'per_page' => 50,

    /**
     * Batas jumlah hari untuk rentang custom, menjaga query tetap ringan.
     * Tidak ada gunanya melebihi retensi sidecar — data yang lebih tua sudah
     * dihapus.
     */
    'max_custom_days' => 366,
];

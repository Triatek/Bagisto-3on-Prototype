<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat perubahan stok per SKU, untuk halaman
 * "Reporting › Riwayat Sinkronisasi".
 *
 * Diisi oleh sidecar stock-sync (services/sync-history.js), TIDAK oleh Bagisto.
 * Bagisto hanya membacanya.
 *
 * Catatan desain — jangan diubah tanpa membaca ini:
 *
 * 1. SATU BARIS = SATU SIKLUS REKONSILIASI SATU SKU, bukan satu order.
 *    Sidecar mendeteksi penjualan marketplace dengan membandingkan stok saat ini
 *    terhadap snapshot siklus sebelumnya (stock-ledger.json). Tiga order yang
 *    masuk di antara dua siklus cron karena itu muncul sebagai satu baris
 *    "terjual 3", bukan tiga baris. Untuk daftar per-order, sumbernya tabel
 *    `marketplace_orders`.
 *
 * 2. BARIS HANYA DIBUAT SAAT ADA PERUBAHAN. Cron berjalan tiap 15 menit atas
 *    semua varian; kalau siklus tanpa perubahan juga dicatat, tabel ini terisi
 *    puluhan ribu baris kosong per hari tanpa informasi apa pun.
 *
 * 3. `reasons` MENYIMPAN KODE MENTAH (mis. 'purchase_shopee,restock'), bukan teks
 *    siap tampil, dan bisa berisi lebih dari satu kode. Penerjemahan terjadi saat
 *    render (lang/{locale}/synchistory.php) — sama seperti `order_status` di
 *    `marketplace_orders`, supaya tulisannya bisa diubah tanpa menyentuh data.
 *
 * 4. `bagisto_manual` adalah SISA perubahan stok Bagisto setelah order toko
 *    sendiri dijelaskan — inilah yang memisahkan "dikurangi admin" dari
 *    "terjual di toko sendiri". Positif = admin menambah, negatif = admin
 *    mengurangi. Dihitung sebagai (delta stok Bagisto + qty order toko), karena
 *    order toko menurunkan stok.
 *
 * 5. `occurred_at` disimpan dalam waktu lokal Asia/Jakarta, sama seperti
 *    `marketplace_orders.ordered_at` dan `orders.created_at` — supaya ketiganya
 *    bisa difilter dengan rentang tanggal yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_sync_history', function (Blueprint $table) {
            $table->id();

            // SKU varian Bagisto (produk simple). Mixed-case, disimpan apa adanya.
            $table->string('sku', 100);

            // Varian yang bersangkutan. Nullable karena riwayat harus tetap utuh
            // (dan tetap bisa dibaca) meski produknya kemudian dihapus.
            $table->unsignedInteger('product_id')->nullable();

            /**
             * Asal pemicu: 'cron' | 'webhook' | 'startup' | 'manual'.
             *
             * Bukan bernama `trigger` — itu kata kunci yang dicadangkan MySQL.
             */
            $table->string('trigger_source', 20)->default('cron');

            // Stok sebelum (= snapshot siklus lalu) dan sesudah, per channel.
            // Nullable: channel yang tidak terhubung untuk SKU ini tetap kosong,
            // bukan 0 — 0 berarti "stok habis", bukan "tidak ada datanya".
            $table->integer('bagisto_before')->nullable();
            $table->integer('bagisto_after')->nullable();
            $table->integer('shopee_before')->nullable();
            $table->integer('shopee_after')->nullable();
            $table->integer('tiktok_before')->nullable();
            $table->integer('tiktok_after')->nullable();

            // Rincian sebab, sebagai angka. Kolom before/after saja tidak cukup:
            // satu siklus bisa punya beberapa sebab sekaligus (mis. terjual 1 di
            // Shopee sementara admin menambah 5), dan netto-nya menyembunyikan itu.
            $table->unsignedInteger('shopee_sold')->default(0);
            $table->unsignedInteger('tiktok_sold')->default(0);
            $table->unsignedInteger('store_sold')->default(0);

            // Bertanda: positif = penambahan admin, negatif = pengurangan admin.
            $table->integer('bagisto_manual')->default(0);

            // Stok final yang disepakati untuk semua channel pada siklus ini.
            $table->integer('target_stock')->nullable();

            // Channel yang benar-benar di-update, mis. 'shopee,tiktok'.
            $table->string('pushed', 40)->default('');

            // 'ok' | 'partial' | 'error' — 'partial' berarti sebagian channel
            // berhasil di-update dan sebagian gagal.
            $table->string('status', 10)->default('ok');

            // Kode sebab dipisah koma — lihat catatan desain no. 3.
            $table->string('reasons', 120);

            // Keterangan bebas: nomor order toko, channel yang gagal di-push, dsb.
            $table->string('note', 255)->nullable();

            // Waktu lokal Asia/Jakarta — lihat catatan desain no. 5.
            $table->dateTime('occurred_at');

            $table->timestamps();

            // Query halaman ini selalu "rentang tanggal, terbaru dulu",
            // sering dipersempit ke satu SKU.
            $table->index(['sku', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sync_history');
    }
};

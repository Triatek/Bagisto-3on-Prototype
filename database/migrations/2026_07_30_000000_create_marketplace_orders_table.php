<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel ringkasan order marketplace (Shopee / TikTok Shop) untuk dashboard
 * "Reporting › Multi-Channel".
 *
 * Diisi oleh sidecar stock-sync (services/order-collector.js), TIDAK oleh Bagisto.
 * Bagisto hanya membacanya.
 *
 * Catatan desain — dua hal yang sengaja dipilih dan jangan diubah tanpa membaca ini:
 *
 * 1. Tabel ini TERPISAH dari tabel `orders` Bagisto dan tidak pernah menulis ke
 *    sana. Order marketplace tidak punya padanan customer/address/invoice/shipment
 *    yang dituntut `orders`, dan memasukkannya ke sana akan mengacaukan laporan
 *    bawaan Bagisto serta berisiko memotong stok dua kali — pemotongan stok sudah
 *    ditangani pipeline sync di sidecar. Angka "semua channel" dihitung dengan
 *    menjumlahkan hasil query tabel ini dengan query tabel `orders`.
 *
 * 2. `order_status` menyimpan status MENTAH dari marketplace (mis. 'COMPLETED',
 *    'CANCELLED', 'AWAITING_SHIPMENT'), tanpa penerjemahan. Definisi status mana
 *    yang dihitung sebagai penjualan ditentukan saat query di sisi Bagisto, bukan
 *    saat penyimpanan — supaya definisinya bisa diubah tanpa menarik ulang data
 *    dari API marketplace.
 *
 * `total_amount` adalah total yang dibayar pembeli (omzet bruto) — BELUM dipotong
 * komisi platform, ongkir, atau voucher seller. Angka ini tidak akan sama dengan
 * settlement report marketplace dan bukan untuk rekonsiliasi keuangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();

            // 'shopee' | 'tiktok'
            $table->string('channel', 20);

            // order_sn (Shopee) / order id (TikTok)
            $table->string('channel_order_id', 64);

            // Status mentah dari marketplace — lihat catatan desain no. 2 di atas.
            $table->string('order_status', 40);

            // Omzet bruto yang dibayar pembeli, sudah termasuk ongkir yang
            // ditanggung pembeli (sebanding dengan `orders.base_grand_total`).
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->char('currency', 3)->default('IDR');

            $table->unsignedInteger('item_count')->default(0);

            /**
             * Waktu order dibuat, disimpan dalam waktu lokal Asia/Jakarta.
             *
             * PENTING: marketplace mengirim create_time sebagai epoch UTC,
             * sedangkan Laravel menulis `orders.created_at` dalam APP_TIMEZONE
             * (Asia/Jakarta). Konversi dilakukan di sidecar sebelum insert supaya
             * kedua sumber bisa difilter dengan rentang tanggal yang sama —
             * tanpa itu angka "hari ini" bergeser 7 jam.
             */
            $table->dateTime('ordered_at');

            $table->timestamps();

            // Kunci idempotensi: puller menarik ulang order yang sama setiap
            // siklus (rolling window) untuk menangkap perubahan status, lalu
            // upsert lewat unique key ini.
            $table->unique(['channel', 'channel_order_id']);

            // Query dashboard selalu berbentuk "rentang tanggal, dipecah per channel".
            $table->index(['channel', 'ordered_at']);
            $table->index('ordered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_orders');
    }
};

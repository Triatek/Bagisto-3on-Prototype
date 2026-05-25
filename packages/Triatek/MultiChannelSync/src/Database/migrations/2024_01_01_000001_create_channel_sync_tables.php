<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Tabel utama: mapping produk Bagisto ↔ Marketplace ───────────
        Schema::create('channel_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bagisto_product_id')->index();
            $table->string('channel');                    // 'shopee' | 'tiktok'
            $table->string('channel_product_id');         // item_id / product_id di marketplace
            $table->string('channel_sku_id')->nullable(); // sku_id (untuk produk dengan variasi)
            $table->enum('status', ['synced', 'pending', 'failed'])->default('pending');
            $table->text('last_error')->nullable();       // pesan error terakhir jika gagal
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['bagisto_product_id', 'channel']);
        });

        // ─── Tabel log stok: rekam setiap perubahan stok ─────────────────
        Schema::create('channel_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bagisto_product_id');
            $table->string('channel');
            $table->integer('qty_before');
            $table->integer('qty_after');
            $table->enum('result', ['success', 'failed'])->default('success');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // ─── Tabel order marketplace: order dari Shopee/TikTok ───────────
        Schema::create('channel_orders', function (Blueprint $table) {
            $table->id();
            $table->string('channel');                    // 'shopee' | 'tiktok'
            $table->string('channel_order_id')->unique(); // order_sn / order_id
            $table->unsignedBigInteger('bagisto_order_id')->nullable();
            $table->enum('status', ['received', 'processing', 'synced', 'failed'])->default('received');
            $table->json('raw_payload');                  // payload asli dari webhook
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_orders');
        Schema::dropIfExists('channel_stock_logs');
        Schema::dropIfExists('channel_products');
    }
};

<?php

namespace Triatek\MultiChannelSync\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class MultiChannelSyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/multichannel.php',
            'multichannel'
        );
    }

    public function boot(): void
    {
        // Publikasikan config
        $this->publishes([
            __DIR__ . '/../Config/multichannel.php' => config_path('multichannel.php'),
        ], 'multichannel-config');

        // Publikasikan migrations
        $this->publishes([
            __DIR__ . '/../Database/migrations/' => database_path('migrations'),
        ], 'multichannel-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        // ─────────────────────────────────────────────────────────
        //  BAGISTO EVENTS — dipanggil otomatis oleh Bagisto core
        // ─────────────────────────────────────────────────────────

        // Produk baru dibuat → push ke semua marketplace
        Event::listen(
            'catalog.product.create.after',
            \Triatek\MultiChannelSync\Listeners\ProductCreatedListener::class
        );

        // Produk diupdate (nama, deskripsi, harga) → update di marketplace
        Event::listen(
            'catalog.product.update.after',
            \Triatek\MultiChannelSync\Listeners\ProductUpdatedListener::class
        );

        // Stok berubah (order masuk, adjustment) → selaraskan stok marketplace
        Event::listen(
            'catalog.product.update.after',
            \Triatek\MultiChannelSync\Listeners\StockSyncListener::class
        );

        // Order masuk dari marketplace (via webhook) → buat order di Bagisto
        Event::listen(
            \Triatek\MultiChannelSync\Events\MarketplaceOrderReceived::class,
            \Triatek\MultiChannelSync\Listeners\MarketplaceOrderListener::class
        );
    }
}

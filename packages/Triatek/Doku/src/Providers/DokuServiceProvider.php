<?php

namespace Triatek\Doku\Providers;

use Illuminate\Support\ServiceProvider;

class DokuServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     * Fungsi ini dijalankan setelah semua service provider lain didaftarkan.
     * Cocok untuk memuat file eksternal seperti Routes, Views, atau Translations.
     */
    public function boot()
    {
        // Memuat file routes.php agar URL webhook (doku/notification) bisa diakses
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
    }

    /**
     * Register services.
     * Fungsi ini dijalankan paling pertama untuk mendaftarkan konfigurasi ke dalam memori aplikasi.
     */
    public function register()
    {
        // 1. Mendaftarkan Doku ke dalam daftar metode pembayaran Bagisto
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/paymentmethods.php', 'payment_methods'
        );

        // 2. Mendaftarkan form isian (Client ID, Secret Key, dll) ke Admin Panel Bagisto
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php', 'core'
        );
    }
}
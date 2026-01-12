<?php

namespace App\Providers;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Midtrans\Config; // <--- PENTING: Jangan sampai baris ini hilang

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(Request::ip(), $allowedIPs)) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });

        // ============================================================
        // SETUP MIDTRANS (SOLUSI KOMPLIT ERROR KONEKSI & KEY)
        // ============================================================

        // 1. Ambil status environment (sandbox/production)
        $env = env('MIDTRANS_ENVIRONMENT', 'sandbox');

        // 2. Set Konfigurasi Kunci (Supaya tidak null)
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // 3. Tentukan Mode Production atau Sandbox
        // Jika di .env tertulis 'production', maka set TRUE. Selain itu FALSE.
        Config::$isProduction = ($env === 'production');

        // 4. "NUCLEAR" SSL BYPASS (Solusi Error 10023 / Connection Failed di Windows)
        // Kita matikan pengecekan SSL hanya jika sedang di Localhost
        if (env('APP_ENV') === 'local') {
            Config::$curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
            Config::$curlOptions[CURLOPT_SSL_VERIFYHOST] = 0; // Abaikan nama host
            Config::$curlOptions[CURLOPT_CONNECTTIMEOUT] = 30; // Perpanjang waktu tunggu
        }
    }
}
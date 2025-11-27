<?php

namespace Triatek\RajaOngkir\Providers;

use Illuminate\Support\ServiceProvider;

class RajaOngkirServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     */
    public function register()
    {
        // 1. Merge Config Kurir (carriers.php)
        // Ini menggantikan Config::set() manual yang kita pakai sebelumnya
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/carriers.php', 'carriers'
        );

        // 2. Merge Config Admin (system.php)
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php', 'core'
        );
    }
}
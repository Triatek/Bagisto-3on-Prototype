<?php

namespace App\Providers;

use App\Listeners\MarketplacePublishHook;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;


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

        // Produk disimpan admin → beri tahu sidecar stock-sync agar produk yang
        // di-opt-in langsung diunggah ke marketplace (tanpa menunggu cron).
        // Sengaja update.after, bukan create.after — lihat catatan di listener.
        Event::listen('catalog.product.update.after', MarketplacePublishHook::class);

        $this->registerMultiChannelReportMenu();
    }

    /**
     * Daftarkan halaman laporan tambahan ("Multi-Channel", "Riwayat Sinkronisasi")
     * ke sidebar admin.
     *
     * Ditambahkan lewat config dari sini, bukan dengan mengedit
     * packages/Webkul/Admin/src/Config/{menu,acl}.php, supaya perubahan tidak
     * hilang saat package Bagisto diperbarui.
     *
     * Entri `acl` WAJIB ada berpasangan dengan entri menu: Webkul\Core\Menu
     * memfilter setiap item lewat bouncer()->hasPermission($item['key']), jadi
     * tanpa ACL menunya tidak akan pernah muncul untuk role selain super-admin —
     * dan entri ini juga yang memunculkannya di form izin role.
     *
     * Dijalankan di boot() karena AdminServiceProvider melakukan
     * mergeConfigFrom() pada register(); menyisipkan di register() bisa tertimpa.
     */
    protected function registerMultiChannelReportMenu(): void
    {
        $entries = [
            [
                'key'   => 'reporting.multichannel',
                'name'  => 'multichannel.menu',
                'route' => 'admin.reporting.multichannel.index',
                'sort'  => 4,
            ],
            [
                'key'   => 'reporting.synchistory',
                'name'  => 'synchistory.menu',
                'route' => 'admin.reporting.synchistory.index',
                'sort'  => 5,
            ],
        ];

        config([
            'menu.admin' => array_merge(
                config('menu.admin', []),
                array_map(fn ($entry) => $entry + ['icon' => ''], $entries)
            ),

            'acl' => array_merge(
                config('acl', []),
                $entries
            ),
        ]);
    }
}
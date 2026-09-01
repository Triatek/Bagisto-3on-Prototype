<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard laporan penjualan multi-channel (Bagisto + Shopee + TikTok Shop).
 *
 * Read-only, informasi cepat saja. Dua sumber data:
 *   - Tabel `orders` Bagisto  → penjualan toko sendiri (data yang sudah ada)
 *   - Tabel `marketplace_orders` → diisi sidecar stock-sync dari Order API
 *
 * Kedua tabel menyimpan waktu dalam APP_TIMEZONE (Asia/Jakarta), jadi keduanya
 * bisa difilter dengan rentang tanggal yang sama. Sidecar sudah mengonversi
 * epoch UTC dari marketplace ke waktu lokal sebelum menyimpan.
 */
class MultiChannelReportController extends Controller
{
    /**
     * Channel yang ditampilkan, dalam urutan tetap.
     *
     * Urutan ini juga menentukan slot warna di view — warna mengikuti entitas,
     * bukan peringkatnya, supaya Shopee selalu oranye meski nilainya berubah.
     */
    public const CHANNELS = ['bagisto', 'shopee', 'tiktok'];

    public function index(Request $request)
    {
        [$start, $end, $period] = $this->resolvePeriod($request);

        $totals = $this->totalsByChannel($start, $end);
        $daily = $this->dailyTrend($start, $end);

        $grandTotal = [
            'orders' => array_sum(array_column($totals, 'orders')),
            'total'  => array_sum(array_column($totals, 'total')),
        ];

        return view('admin.reporting.multichannel', [
            'period'     => $period,
            'start'      => $start,
            'end'        => $end,
            'totals'     => $totals,
            'grandTotal' => $grandTotal,
            'daily'      => $daily,
            // Puncak tren dipakai view untuk menskalakan tinggi batang.
            'dailyPeak'  => max(1, max(array_map(fn ($d) => $d['total'], $daily)) ?: 1),
        ]);
    }

    /**
     * Tentukan rentang tanggal dari query string.
     *
     * Semua Carbon di sini memakai APP_TIMEZONE, jadi "hari ini" berarti hari ini
     * menurut WIB — bukan UTC.
     */
    protected function resolvePeriod(Request $request): array
    {
        $period = $request->query('period', '30days');

        if ($period === 'custom') {
            $start = $this->parseDate($request->query('start'));
            $end = $this->parseDate($request->query('end'));

            // Input tidak valid / terbalik → jatuh kembali ke default daripada
            // menampilkan angka yang menyesatkan.
            if (! $start || ! $end || $start->gt($end)) {
                $period = '30days';
            } else {
                $maxDays = (int) config('multichannel-report.max_custom_days', 366);

                if ($start->diffInDays($end) > $maxDays) {
                    $start = $end->copy()->subDays($maxDays);
                }

                return [$start->startOfDay(), $end->endOfDay(), 'custom'];
            }
        }

        $end = Carbon::now()->endOfDay();

        $start = match ($period) {
            'today'  => Carbon::now()->startOfDay(),
            '7days'  => Carbon::now()->subDays(6)->startOfDay(),
            default  => Carbon::now()->subDays(29)->startOfDay(),
        };

        return [$start, $end, in_array($period, ['today', '7days', '30days']) ? $period : '30days'];
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Total nilai & jumlah order per channel untuk rentang tertentu.
     *
     * @return array<string, array{orders: int, total: float}>
     */
    protected function totalsByChannel(Carbon $start, Carbon $end): array
    {
        $result = array_fill_keys(self::CHANNELS, ['orders' => 0, 'total' => 0.0]);

        $bagisto = DB::table('orders')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', $this->excluded('bagisto'))
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(base_grand_total), 0) AS total')
            ->first();

        $result['bagisto'] = [
            'orders' => (int) ($bagisto->orders ?? 0),
            'total'  => (float) ($bagisto->total ?? 0),
        ];

        $rows = $this->marketplaceQuery($start, $end)
            ->groupBy('channel')
            ->selectRaw('channel, COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS total')
            ->get();

        foreach ($rows as $row) {
            $result[$row->channel] = [
                'orders' => (int) $row->orders,
                'total'  => (float) $row->total,
            ];
        }

        return $result;
    }

    /**
     * Tren harian per channel, satu entri per tanggal dalam rentang (termasuk
     * tanggal tanpa penjualan, supaya grafiknya tidak bolong).
     */
    protected function dailyTrend(Carbon $start, Carbon $end): array
    {
        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days[$date->toDateString()] = array_fill_keys(self::CHANNELS, 0.0) + ['total' => 0.0];
        }

        $bagisto = DB::table('orders')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', $this->excluded('bagisto'))
            ->groupByRaw('DATE(created_at)')
            ->selectRaw('DATE(created_at) AS day, COALESCE(SUM(base_grand_total), 0) AS total')
            ->get();

        foreach ($bagisto as $row) {
            if (isset($days[$row->day])) {
                $days[$row->day]['bagisto'] = (float) $row->total;
            }
        }

        $marketplace = $this->marketplaceQuery($start, $end)
            ->groupByRaw('DATE(ordered_at), channel')
            ->selectRaw('DATE(ordered_at) AS day, channel, COALESCE(SUM(total_amount), 0) AS total')
            ->get();

        foreach ($marketplace as $row) {
            if (isset($days[$row->day]) && in_array($row->channel, self::CHANNELS)) {
                $days[$row->day][$row->channel] = (float) $row->total;
            }
        }

        $out = [];

        foreach ($days as $day => $values) {
            $values['total'] = array_sum(array_intersect_key($values, array_flip(self::CHANNELS)));
            $values['day'] = $day;
            $out[] = $values;
        }

        return $out;
    }

    /**
     * Query dasar `marketplace_orders` dengan status yang dikecualikan.
     *
     * Dibentuk sebagai AND dari NOT(channel AND status-dikecualikan), bukan
     * sebagai daftar channel yang diizinkan. Konsekuensinya: channel baru yang
     * belum dikenal tetap ikut terhitung alih-alih hilang diam-diam.
     */
    protected function marketplaceQuery(Carbon $start, Carbon $end)
    {
        $query = DB::table('marketplace_orders')
            ->whereBetween('ordered_at', [$start, $end]);

        foreach (config('multichannel-report.excluded_statuses', []) as $channel => $statuses) {
            if ($channel === 'bagisto' || empty($statuses)) {
                continue;
            }

            $query->whereNot(function ($q) use ($channel, $statuses) {
                $q->where('channel', $channel)->whereIn('order_status', $statuses);
            });
        }

        return $query;
    }

    protected function excluded(string $key): array
    {
        return config("multichannel-report.excluded_statuses.$key", []);
    }
}

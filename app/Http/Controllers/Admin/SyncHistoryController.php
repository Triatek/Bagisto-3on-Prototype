<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Riwayat sinkronisasi stok (Bagisto ↔ Shopee ↔ TikTok Shop).
 *
 * Read-only. Sumber datanya satu: tabel `stock_sync_history`, yang diisi sidecar
 * stock-sync setiap kali sebuah SKU benar-benar berubah — lihat catatan desain di
 * file migrasinya.
 *
 * Yang membedakan halaman ini dari dashboard Multi-Channel: di sini yang dilacak
 * adalah PERGERAKAN STOK beserta sebabnya (pembelian / penambahan / pengurangan),
 * bukan omzet. Satu baris = satu siklus rekonsiliasi satu SKU, bukan satu order.
 */
class SyncHistoryController extends Controller
{
    /**
     * Kode sebab yang bisa dipakai sebagai filter, dalam urutan tampil.
     *
     * Harus sama dengan konstanta REASON di sidecar
     * (stock-sync/services/sync-history.js). Kodenya disimpan mentah di kolom
     * `reasons`; labelnya diambil dari lang/{locale}/synchistory.php.
     */
    public const REASONS = [
        'purchase_shopee',
        'purchase_tiktok',
        'purchase_store',
        'restock',
        'reduction',
        'calibration',
        'resync',
    ];

    public function index(Request $request)
    {
        [$start, $end, $period] = $this->resolvePeriod($request);

        $filters = [
            'sku'    => trim((string) $request->query('sku', '')),
            'reason' => (string) $request->query('reason', ''),
            'status' => (string) $request->query('status', ''),
        ];

        if (! in_array($filters['reason'], self::REASONS)) {
            $filters['reason'] = '';
        }

        if (! in_array($filters['status'], ['ok', 'partial', 'error'])) {
            $filters['status'] = '';
        }

        $summary = $this->summary($start, $end, $filters);

        $rows = $this->baseQuery($start, $end, $filters)
            // occurred_at menit-an dan bisa sama untuk banyak SKU dalam satu
            // siklus; id sebagai tie-breaker menjaga urutan halaman stabil
            // (tanpa itu, baris bisa muncul dua kali / hilang antar halaman).
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate((int) config('sync-history.per_page', 50))
            ->appends($request->query());

        return view('admin.reporting.sync-history', [
            'period'  => $period,
            'start'   => $start,
            'end'     => $end,
            'filters' => $filters,
            'reasons' => self::REASONS,
            'summary' => $summary,
            'rows'    => $rows,
        ]);
    }

    /**
     * Rentang tanggal dari query string.
     *
     * Semua Carbon di sini memakai APP_TIMEZONE, dan `occurred_at` juga disimpan
     * dalam waktu lokal oleh sidecar — jadi keduanya sudah sepadan.
     */
    protected function resolvePeriod(Request $request): array
    {
        $period = $request->query('period', '7days');

        if ($period === 'custom') {
            $start = $this->parseDate($request->query('start'));
            $end = $this->parseDate($request->query('end'));

            // Input tidak valid / terbalik → jatuh ke default daripada
            // menampilkan rentang yang menyesatkan.
            if ($start && $end && $start->lte($end)) {
                $maxDays = (int) config('sync-history.max_custom_days', 366);

                if ($start->diffInDays($end) > $maxDays) {
                    $start = $end->copy()->subDays($maxDays);
                }

                return [$start->startOfDay(), $end->endOfDay(), 'custom'];
            }

            $period = '7days';
        }

        $end = Carbon::now()->endOfDay();

        $start = match ($period) {
            'today'  => Carbon::now()->startOfDay(),
            '30days' => Carbon::now()->subDays(29)->startOfDay(),
            default  => Carbon::now()->subDays(6)->startOfDay(),
        };

        return [$start, $end, in_array($period, ['today', '7days', '30days']) ? $period : '7days'];
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
     * Query dasar + filter. Dipakai bersama oleh tabel dan ringkasan, supaya
     * angka ringkasan selalu menjelaskan baris yang sedang tampil.
     */
    protected function baseQuery(Carbon $start, Carbon $end, array $filters)
    {
        $query = DB::table('stock_sync_history')
            ->whereBetween('occurred_at', [$start, $end]);

        if ($filters['sku'] !== '') {
            $query->where('sku', 'like', '%'.$filters['sku'].'%');
        }

        if ($filters['reason'] !== '') {
            // `reasons` adalah daftar dipisah koma (satu siklus bisa punya
            // beberapa sebab), jadi FIND_IN_SET — bukan `=` yang hanya cocok
            // kalau sebabnya persis satu.
            $query->whereRaw('FIND_IN_SET(?, reasons)', [$filters['reason']]);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * Ringkasan pergerakan stok untuk rentang & filter yang aktif.
     *
     * Penambahan dan pengurangan admin dipisah dari satu kolom bertanda
     * (`bagisto_manual`): keduanya adalah tindakan yang berbeda, dan
     * menjumlahkannya jadi satu angka netto justru menghapus informasinya.
     */
    protected function summary(Carbon $start, Carbon $end, array $filters): array
    {
        $row = $this->baseQuery($start, $end, $filters)
            ->selectRaw('
                COUNT(*) AS events,
                COUNT(DISTINCT sku) AS skus,
                COALESCE(SUM(shopee_sold), 0) AS shopee_sold,
                COALESCE(SUM(tiktok_sold), 0) AS tiktok_sold,
                COALESCE(SUM(store_sold), 0) AS store_sold,
                COALESCE(SUM(CASE WHEN bagisto_manual > 0 THEN bagisto_manual ELSE 0 END), 0) AS restocked,
                COALESCE(SUM(CASE WHEN bagisto_manual < 0 THEN -bagisto_manual ELSE 0 END), 0) AS reduced,
                COALESCE(SUM(status <> \'ok\'), 0) AS problems
            ')
            ->first();

        return [
            'events'      => (int) ($row->events ?? 0),
            'skus'        => (int) ($row->skus ?? 0),
            'shopee_sold' => (int) ($row->shopee_sold ?? 0),
            'tiktok_sold' => (int) ($row->tiktok_sold ?? 0),
            'store_sold'  => (int) ($row->store_sold ?? 0),
            'restocked'   => (int) ($row->restocked ?? 0),
            'reduced'     => (int) ($row->reduced ?? 0),
            'problems'    => (int) ($row->problems ?? 0),
        ];
    }
}

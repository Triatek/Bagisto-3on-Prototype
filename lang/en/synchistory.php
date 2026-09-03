<?php

return [
    'title' => 'Stock Sync History',
    'menu'  => 'Sync History',

    'subtitle' => 'Every stock change and what caused it',

    'disclaimer' => 'One row = one sync cycle for one SKU, not one order. Several marketplace orders arriving between two cycles are merged into a single row with the combined quantity. For a per-order list, see Reporting › Multi-Channel.',

    'period' => [
        'today'  => 'Today',
        '7days'  => 'Last 7 days',
        '30days' => 'Last 30 days',
        'apply'  => 'Apply',
    ],

    'filter' => [
        'sku'      => 'SKU',
        'sku_hint' => 'search SKU…',
        'reason'   => 'Reason',
        'status'   => 'Status',
        'all'      => 'All',
        'apply'    => 'Filter',
        'reset'    => 'Reset',
    ],

    'summary' => [
        'events'      => 'Changes recorded',
        'events_hint' => ':count SKUs involved',
        'sold'        => 'Sold',
        'sold_hint'   => 'Shopee :shopee · TikTok :tiktok · Store :store',
        'restocked'   => 'Added by admin',
        'reduced'     => 'Removed by admin',
        'unit'        => ':count pcs',
        'problems'    => ':count failed / partial pushes',
        'problems_ok' => 'All pushes succeeded',
    ],

    'table' => [
        'time'    => 'Time',
        'sku'     => 'SKU',
        'trigger' => 'Trigger',
        'bagisto' => 'Bagisto',
        'shopee'  => 'Shopee',
        'tiktok'  => 'TikTok',
        'final'   => 'Final stock',
        'reason'  => 'Reason',
        'none'    => '—',
    ],

    /**
     * Labels for the `reasons` codes. Keys must match the REASON constants in the
     * sidecar (stock-sync/services/sync-history.js) — only the on-screen wording
     * changes here, never the stored data.
     */
    'reason' => [
        'purchase_shopee' => 'Shopee purchase',
        'purchase_tiktok' => 'TikTok purchase',
        'purchase_store'  => 'Own store purchase',
        'restock'         => 'Stock added',
        'reduction'       => 'Stock removed',
        'calibration'     => 'Initial calibration',
        'resync'          => 'Re-aligned',
    ],

    'reason_hint' => [
        'purchase_shopee' => 'Shopee stock dropped since the last cycle — an order came in.',
        'purchase_tiktok' => 'TikTok Shop stock dropped since the last cycle — an order came in.',
        'purchase_store'  => 'There was an order in your own Bagisto store for this SKU.',
        'restock'         => 'Bagisto stock went up with no matching order — an admin added stock.',
        'reduction'       => 'Bagisto stock went down with no matching order — an admin removed stock.',
        'calibration'     => 'First time this SKU was synced; stock aligned to the lowest channel.',
        'resync'          => 'No sale, but a channel had drifted and was re-aligned.',
    ],

    'status' => [
        'ok'      => 'Success',
        'partial' => 'Partial',
        'error'   => 'Failed',
    ],

    'trigger' => [
        'cron'    => 'Scheduled',
        'webhook' => 'Real-time',
        'startup' => 'Startup',
        'manual'  => 'Manual',
    ],

    'empty' => [
        'title'    => 'No stock changes in this period',
        'hint'     => 'Rows are only created when something actually changed — sync cycles that change nothing are deliberately not recorded.',
        'filtered' => 'Nothing matches these filters. Try loosening them or widening the date range.',
    ],
];

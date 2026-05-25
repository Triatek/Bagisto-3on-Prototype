<?php

namespace Triatek\MultiChannelSync\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelProduct extends Model
{
    protected $fillable = [
        'bagisto_product_id',
        'channel',
        'channel_product_id',
        'channel_sku_id',
        'status',
        'last_error',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    // ─── Scope helpers ───────────────────────────────────────────────────

    public function scopeShopee($query)
    {
        return $query->where('channel', 'shopee');
    }

    public function scopeTiktok($query)
    {
        return $query->where('channel', 'tiktok');
    }

    public function scopeSynced($query)
    {
        return $query->where('status', 'synced');
    }

    // ─── Cari mapping berdasarkan Bagisto product ID + channel ───────────

    public static function findMapping(int $bagistoProductId, string $channel): ?self
    {
        return static::where('bagisto_product_id', $bagistoProductId)
            ->where('channel', $channel)
            ->first();
    }

    public function markSynced(): void
    {
        $this->update([
            'status'         => 'synced',
            'last_error'     => null,
            'last_synced_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status'     => 'failed',
            'last_error' => $error,
        ]);
    }
}

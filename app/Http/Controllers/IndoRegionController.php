<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndoRegionController extends Controller
{
    /**
     * URL API wilayah Indonesia (gratis, tanpa API key).
     */
    protected $baseUrl = 'https://wilayah.id/api';

    /**
     * Lama cache hasil API (detik).
     */
    protected $cacheTtl = 604800; // 7 hari

    /**
     * Lokasi salinan data wilayah di dalam repo, dipakai kalau API tidak bisa
     * dihubungi dari server (firewall / DNS / API sedang down).
     */
    protected $fallbackPath = 'resources/data/indo-region';

    public function getProvinces()
    {
        $provinces = Cache::remember('indo_region.provinces', $this->cacheTtl, function () {
            return $this->fetch('/provinces.json');
        });

        if (empty($provinces)) {
            Cache::forget('indo_region.provinces');

            $provinces = $this->fallbackProvinces();
        }

        return response()->json($provinces);
    }

    public function getCities($provinceCode)
    {
        if (! preg_match('/^\d{2}$/', (string) $provinceCode)) {
            return response()->json([]);
        }

        $cities = Cache::remember('indo_region.cities.'.$provinceCode, $this->cacheTtl, function () use ($provinceCode) {
            return $this->fetch('/regencies/'.$provinceCode.'.json');
        });

        if (empty($cities)) {
            Cache::forget('indo_region.cities.'.$provinceCode);

            $cities = $this->fallbackCities($provinceCode);
        }

        return response()->json($cities);
    }

    /**
     * Ambil data dari wilayah.id. Mengembalikan array kosong kalau gagal,
     * supaya checkout tidak pernah error 500 gara-gara API pihak ketiga.
     */
    protected function fetch($path)
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(5)
                ->connectTimeout(3)
                ->retry(2, 200)
                ->get($this->baseUrl.$path);

            if ($response->failed()) {
                Log::warning('IndoRegion: API wilayah.id gagal', [
                    'path'   => $path,
                    'status' => $response->status(),
                ]);

                return [];
            }

            return $response->json()['data'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('IndoRegion: API wilayah.id tidak bisa dihubungi', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function fallbackProvinces()
    {
        return $this->readFallback('provinces.json') ?: [];
    }

    protected function fallbackCities($provinceCode)
    {
        $regencies = $this->readFallback('regencies.json') ?: [];

        return $regencies[$provinceCode] ?? [];
    }

    protected function readFallback($file)
    {
        $path = base_path($this->fallbackPath.'/'.$file);

        if (! is_readable($path)) {
            Log::error('IndoRegion: berkas fallback tidak ditemukan', ['path' => $path]);

            return [];
        }

        return json_decode(file_get_contents($path), true) ?: [];
    }
}

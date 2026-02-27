<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class IndoRegionController extends Controller
{
    // URL API Gratis
    protected $baseUrl = 'https://wilayah.id/api';

    public function getProvinces()
    {
        // PERBAIKAN: Gunakan withoutVerifying() bukan verify(false)
        $response = Http::withoutVerifying()->get($this->baseUrl . '/provinces.json');
        
        return response()->json($response->json()['data'] ?? []);
    }

    public function getCities($provinceCode)
    {
        // PERBAIKAN: Gunakan withoutVerifying() bukan verify(false)
        $response = Http::withoutVerifying()->get($this->baseUrl . '/regencies/' . $provinceCode . '.json');

        return response()->json($response->json()['data'] ?? []);
    }
}
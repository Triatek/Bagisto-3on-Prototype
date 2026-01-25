<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\IndoRegionController;

Route::get('/indo-region/provinces', [IndoRegionController::class, 'getProvinces'])->name('api.provinces');
Route::get('/indo-region/cities/{code}', [IndoRegionController::class, 'getCities'])->name('api.cities');

Route::get('/midtrans/pay', function (Request $request) {
    $token = $request->query('token');
    
    // Ambil Key & Tentukan URL Script JS
    $serverKey = env('MIDTRANS_SERVER_KEY') ?? config('services.midtrans.server_key');
    $clientKey = env('MIDTRANS_CLIENT_KEY') ?? config('services.midtrans.client_key');
    
    // Validasi Sederhana
    if (!$token) return "Error: Token tidak ditemukan.";

    // Auto Detect Mode
    $isProduction = (strpos($serverKey, 'Mid-') === 0);
    $snapUrl = $isProduction 
        ? 'https://app.midtrans.com/snap/snap.js' 
        : 'https://app.sandbox.midtrans.com/snap/snap.js';

    return view('midtrans.pay', compact('token', 'snapUrl', 'clientKey'));
})->name('midtrans.snap_page');
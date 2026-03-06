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

Route::get('/fast-stock-sync', function (Request $request) {
    // 1. Keamanan: Cek kunci rahasia 
    if ($request->query('key') !== 'super-rahasia-3on') {
        return response()->json(['error' => 'Akses ditolak !'], 401);
    }

    $productId = $request->query('product_id');
    $qty = $request->query('qty');

    // 2. Update stok langsung ke database gudang (ID Gudang = 1)
    \Webkul\Product\Models\ProductInventory::updateOrCreate(
        ['product_id' => $productId, 'inventory_source_id' => 1],
        ['qty' => $qty]
    );

    // 3. Sentil cache Bagisto agar toko depan langsung update
    $product = \Webkul\Product\Models\Product::find($productId);
    if ($product) {
        \Illuminate\Support\Facades\Event::dispatch('catalog.product.update.after', $product);
    }

    return response()->json([
        'success' => true, 
        'message' => 'Stok aman terupdate tanpa menyentuh foto/kategori!'
    ]);
});
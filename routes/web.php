<?php

use Illuminate\Support\Facades\Route;

// ... (kode route bawaan lainnya) ...

// 👇 TEMPEL DI SINI 👇
Route::get('/cek-kurir', function () {
    // Kita lihat daftar semua kurir yang dikenali Bagisto
    dd(config('carriers'));
});
<?php

use Illuminate\Support\Facades\Route;
use Triatek\Doku\Http\Controllers\DokuController;

Route::group(['prefix' => 'doku'], function () {
    // Jalur tanpa proteksi CSRF karena diakses otomatis oleh server Doku
    Route::post('notification', [DokuController::class, 'handleNotification'])
        ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
        ->name('doku.notification');

    // Jalur untuk customer kembali setelah selesai bayar di Doku
    Route::get('return', [DokuController::class, 'handleReturn'])->name('doku.return');
});
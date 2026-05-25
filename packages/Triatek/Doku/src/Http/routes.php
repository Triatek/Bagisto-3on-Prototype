<?php

use Illuminate\Support\Facades\Route;
use Triatek\Doku\Http\Controllers\DokuController;

Route::group(['prefix' => 'doku'], function () {
    // Jalur tanpa proteksi CSRF karena diakses otomatis oleh server Doku
    Route::post('notification', [DokuController::class, 'handleNotification'])->name('doku.notification');
});
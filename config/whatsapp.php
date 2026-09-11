<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tombol WhatsApp Mengambang
    |--------------------------------------------------------------------------
    |
    | Nomor tujuan tombol WhatsApp yang mengambang di kanan bawah halaman toko.
    | Tulis dalam format internasional tanpa "+" atau spasi, contoh: 628123456789.
    |
    */

    'number' => env('WHATSAPP_NUMBER', ''),

    'message' => env('WHATSAPP_MESSAGE', "Halo Admin 3ON, aku mau tanya-tanya soal produknya dong, boleh dibantu ya \u{1F60A}"),
];

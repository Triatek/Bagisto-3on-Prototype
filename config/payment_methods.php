<?php

return [

    'midtrans' => [
        'code'        => 'midtrans',
        'title'       => 'Midtrans (Semua Pembayaran)',
        'description' => 'Pay securely via Midtrans',
        'class'       => \Akara\MidtransPayment\Payment\MidtransPayment::class,
        'active'      => true,
        'sort'        => 1,
        'image'       => null,
    ],
];
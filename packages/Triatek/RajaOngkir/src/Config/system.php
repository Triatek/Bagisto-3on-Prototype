<?php

return [
    [
        'key'    => 'sales.carriers.rajaongkir',
        'name'   => 'RajaOngkir',
        'sort'   => 1,
        'info'   => 'Pengaturan API RajaOngkir (Komerce)',
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'admin::app.admin.system.title',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => true,
                'locale_based'  => true,
                'info'          => 'Judul di halaman checkout'
            ], 
            [
                'name'          => 'description',
                'title'         => 'admin::app.admin.system.description',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
                'info'          => 'Deskripsi singkat'
            ],
            [
                'name'          => 'active',
                'title'         => 'admin::app.admin.system.status',
                'type'          => 'boolean',
                'validation'    => 'required',
                'channel_based' => true,
                'locale_based'  => true,
                'info'          => 'Status Aktif'
            ],
            [
                'name'          => 'api_key',
                'title'         => 'API Key (Komerce)',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
                'info'          => 'Masukkan API Key dari komerce.id'
            ],
            [
                'name'          => 'origin_city',
                'title'         => 'ID Kota Asal',
                'type'          => 'text',
                'validation'    => 'required|numeric',
                'channel_based' => false,
                'locale_based'  => false,
                'info'          => 'ID Kota (Angka) contoh: 152'
            ],
        ]
    ],
    [
        'key'    => 'sales.carriers.rajaongkir',
        'name'   => 'admin::app.admin.system.rajaongkir',
        'sort'   => 1,
        'fields' => [
            // ... field lain (title, description, active) ...
            [
                'name'          => 'api_key', // <--- Ini yang dibaca kode tadi
                'title'         => 'API Key',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'origin_city', // <--- Ini yang dibaca kode tadi (ID Kota Asal)
                'title'         => 'Origin City ID (Angka)',
                'type'          => 'text',
                'validation'    => 'required|numeric',
                'channel_based' => true,
                'locale_based'  => false,
            ],
        ],
    ],
];
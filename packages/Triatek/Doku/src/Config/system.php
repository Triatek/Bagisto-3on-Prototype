<?php

return [
    [
        'key'    => 'sales.payment_methods.doku',
        'name'   => 'Doku Payment Gateway',
        'sort'   => 3,
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'Title',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => true,
                'locale_based'  => true,
            ], [
                'name'          => 'image',
                'title'         => 'Upload Logo',
                'type'          => 'image',
                'channel_based' => false,
                'locale_based'  => false,
                'validation'    => 'mimes:bmp,jpeg,jpg,png,webp',
            ], [
                'name'          => 'client_id',
                'title'         => 'Client ID',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => true,
            ], [
                'name'          => 'secret_key',
                'title'         => 'Secret Key',
                'type'          => 'password',
                'validation'    => 'required',
                'channel_based' => true,
            ], [
                'name'          => 'sandbox',
                'title'         => 'Sandbox Mode',
                'type'          => 'boolean',
                'channel_based' => true,
            ], [
                'name'          => 'active',
                'title'         => 'Status',
                'type'          => 'boolean',
                'validation'    => 'required',
                'channel_based' => true,
            ]
        ]
    ]
];
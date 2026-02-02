<?php

return [
    [
        'key'    => 'sales.payment_methods.midtrans',
        'name'   => 'Midtrans Payment',
        'sort'   => 100,
        'fields' => [
            // --- 1. DATA UTAMA (WAJIB) ---
            [
                'name'          => 'title',
                'title'         => 'Judul Pembayaran',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
            ],
            [
                'name'          => 'description',
                'title'         => 'Deskripsi',
                'type'          => 'textarea',
                'channel_based' => false,
                'locale_based'  => true,
            ],
            
            // --- 2. KUNCI RAHASIA MIDTRANS ---
            [
                'name'          => 'server_key',
                'title'         => 'Server Key',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
            ],
            [
                'name'          => 'client_key',
                'title'         => 'Client Key',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
            ],
            [
                'name'          => 'sandbox',
                'title'         => 'Mode Sandbox (Testing)',
                'type'          => 'boolean',
                'channel_based' => false,
                'locale_based'  => false,
            ],

            // --- 3. TAMPILAN & LOGO ---
            [
                'name'          => 'image',
                'title'         => 'Upload Logo',
                'type'          => 'image',
                'channel_based' => false,
                'locale_based'  => false,
                'validation'    => 'mimes:bmp,jpeg,jpg,png,webp',
            ],
            
            // --- 4. INSTRUKSI TAMBAHAN ---
            [
                'name'          => 'instructions',
                'title'         => 'Instruksi Pembayaran',
                'type'          => 'textarea',
                'channel_based' => false,
                'locale_based'  => true,
                'info'          => 'Pesan ini akan tampil di halaman checkout atau email konfirmasi.',
            ],

            // --- 5. PENGATURAN INVOICE OTOMATIS (YANG ANDA CARI) ---
            [
                'name'          => 'generate_invoice',
                'title'         => 'Otomatis Buat Invoice setelah Order?',
                'type'          => 'boolean',
                'default_value' => false,
                'channel_based' => false,
                'locale_based'  => false,
            ],
            [
                'name'    => 'invoice_status',
                'title'   => 'Set Status Invoice ke:',
                'type'    => 'select',
                'options' => [
                    [
                        'title' => 'Pending',
                        'value' => 'pending',
                    ],
                    [
                        'title' => 'Paid (Lunas)',
                        'value' => 'paid',
                    ],
                ],
                'depends' => 'generate_invoice:1', // Hanya muncul jika generate_invoice dipilih YES
            ],
            [
                'name'    => 'order_status',
                'title'   => 'Set Status Order ke:',
                'type'    => 'select',
                'options' => [
                    [
                        'title' => 'Pending',
                        'value' => 'pending',
                    ],
                    [
                        'title' => 'Processing',
                        'value' => 'processing',
                    ],
                    [
                        'title' => 'Completed',
                        'value' => 'completed',
                    ],
                ],
                'depends' => 'generate_invoice:1', // Hanya muncul jika generate_invoice dipilih YES
            ],

            // --- 6. URUTAN & STATUS ---
            [
                'name'          => 'sort',
                'title'         => 'Urutan Tampil (Sort Order)',
                'type'          => 'text',
                'validation'    => 'numeric',
                'channel_based' => false,
                'locale_based'  => false,
            ],
            [
                'name'          => 'active',
                'title'         => 'Status Aktif',
                'type'          => 'boolean',
                'channel_based' => false,
                'locale_based'  => false,
            ],
        ]
    ]
];
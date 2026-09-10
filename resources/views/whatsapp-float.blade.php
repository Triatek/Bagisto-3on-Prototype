@php
    // Bersihkan nomor: buang selain digit, lalu ubah awalan 0 menjadi 62.
    $whatsappNumber = preg_replace('/\D/', '', (string) config('whatsapp.number'));

    if (str_starts_with($whatsappNumber, '0')) {
        $whatsappNumber = '62' . substr($whatsappNumber, 1);
    }
@endphp

@if ($whatsappNumber)
    <style>
        .wa-float {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 9999px;
            background-color: #25d366;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            transition: transform 0.2s ease;
        }

        .wa-float:hover {
            transform: scale(1.08);
        }

        .wa-float svg {
            width: 32px;
            height: 32px;
            fill: #ffffff;
        }

        /*
         * Di bawah 768px tema Bagisto memunculkan bar aksi melekat di dasar layar
         * (bar filter/urutkan halaman kategori, lihat categories/filters.blade.php
         * yang memakai `isMobile: window.innerWidth <= 767`). Tombol dinaikkan agar
         * tidak menutupinya. Modal & drawer memakai z-index 999-1000, jadi tetap
         * berada di atas tombol ini.
         */
        @media (max-width: 767px) {
            .wa-float {
                right: 16px;
                bottom: 76px;
                width: 50px;
                height: 50px;
            }

            .wa-float svg {
                width: 28px;
                height: 28px;
            }
        }
    </style>

    <a
        class="wa-float"
        href="https://wa.me/{{ $whatsappNumber }}?text={{ rawurlencode(config('whatsapp.message')) }}"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Hubungi kami lewat WhatsApp"
        title="Hubungi kami lewat WhatsApp"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 016.988 2.898 9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>
@endif

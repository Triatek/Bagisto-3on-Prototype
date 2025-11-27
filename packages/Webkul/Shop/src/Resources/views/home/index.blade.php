{{-- 1. LOGIC PHP: MENGAMBIL PRODUK TERBARU (URUTAN UPLOAD) --}}
@inject('productFlatRepository', 'Webkul\Product\Repositories\ProductFlatRepository')

@php
    $channel = core()->getCurrentChannel();
    $locale = core()->getCurrentLocale();

    // Query produk terbaru dari database
    $newArrivals = $productFlatRepository->scopeQuery(function ($query) use ($channel, $locale) {
        return $query->where('status', 1)
                     ->where('visible_individually', 1)
                     ->where('channel', $channel->code)
                     ->where('locale', $locale->code)
                     ->orderBy('created_at', 'desc'); 
    })->paginate(8);
@endphp

@push ('meta')
    <meta name="title" content="{{ $channel->home_seo['meta_title'] ?? '' }}" />
    <meta name="description" content="{{ $channel->home_seo['meta_description'] ?? '' }}" />
    <meta name="keywords" content="{{ $channel->home_seo['meta_keywords'] ?? '' }}" />
@endPush

<x-shop::layouts>
    <x-slot:title>
        {{  $channel->home_seo['meta_title'] ?? '' }}
    </x-slot>

    @foreach ($customizations as $customization)
        @php ($data = $customization->options) @endphp

        @switch ($customization->type)
            {{-- SLIDER UTAMA --}}
            @case ($customization::IMAGE_CAROUSEL)
                <x-shop::carousel :options="$data" aria-label="Image Carousel"/>
                @break

            {{-- KONTEN STATIS (TEMPAT KITA MENYUNTIKKAN LOGIC) --}}
            @case ($customization::STATIC_CONTENT)
                @if (! empty($data['css']))
                    @push ('styles') <style>{{ $data['css'] }}</style> @endpush
                @endif

                @if (! empty($data['html']))
                    
                    {{-- CEK: Apakah ini Widget New Arrivals Statis Anda? (Dideteksi dari class CSS-nya) --}}
                    @if (Str::contains($data['html'], 'na-wrapper-luar'))
                        
                        {{-- JIKA YA: Hiraukan HTML statis, ganti dengan DATA DINAMIS --}}
                        @if($newArrivals->count() > 0)
                            <div class="na-wrapper-luar">
                                <div class="na-container-dalam">
                                    <div class="na-header">
                                        <h2 class="na-title">New Arrivals</h2>
                                        <div class="na-underline"></div>
                                    </div>
                            
                                    {{-- Grid Produk Dinamis --}}
                                    <div class="na-grid">
                                        @foreach ($newArrivals as $item)
                                            @php
                                                $image = product_image()->getProductBaseImage($item);
                                            @endphp
                                            <div class="na-card"> 
                                                <a href="{{ route('shop.product_or_category.index', $item->url_key) }}">
                                                    {{-- PERBAIKAN: Menggunakan original_image_url agar gambar muncul --}}
                                                    <div class="na-image-box" style="background-image: url('{{ $image['original_image_url'] }}');">
                                                        <span class="na-badge-new">New Arrival</span>
                                                    </div>
                                                </a>
                                                <a href="{{ route('shop.product_or_category.index', $item->url_key) }}" class="na-prod-name">
                                                    {{ $item->name }}
                                                </a>
                                                <div class="na-prod-price">
                                                    {!! $item->getTypeInstance()->getPriceHtml() !!}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                            
                                    <div class="na-btn-wrapper"> 
                                        <a href="#" class="na-btn">View More</a>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @else
                        {{-- JIKA BUKAN: Tampilkan widget statis lain seperti biasa --}}
                        @if (Str::contains($data['html'], 'public/storage'))
                             {!! str_replace('public/storage', 'storage', $data['html']) !!}
                        @else
                             {!! $data['html'] !!}
                        @endif
                    @endif

                @endif
                @break

            {{-- SLIDER KATEGORI --}}
            @case ($customization::CATEGORY_CAROUSEL)
                <x-shop::categories.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.categories.index', $data['filters'] ?? [])"
                    :navigation-link="route('shop.home.index')"
                    aria-label="Category Carousel"
                />
                @break
        @endswitch
    @endforeach

</x-shop::layouts>

{{-- CSS STYLE CUSTOM --}}
@push('styles')
    <style>
        .na-wrapper-luar { width: 100%; display: flex; justify-content: center; background-color: #fff; padding: 60px 0; }
        .na-container-dalam { width: 100%; max-width: 1440px !important; padding: 0 20px; font-family:'DM Sans', sans-serif; box-sizing: border-box; }
        .na-header { text-align: center; margin-bottom: 50px; display: flex; flex-direction: column; align-items: center; position: relative; }
        .na-title { font-size: 48px; font-weight: 700; color: #000033; font-family:'Playfair Display', serif; margin: 0; line-height: 1; z-index: 2; position: relative; }
        .na-underline { display: block; width: 220px; height: 18px; background-color: #FFF9C4; margin-top: -12px; margin-bottom: 20px; border-radius: 50%; transform: rotate(-2deg); z-index: 1; }
        .na-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 50px; margin-bottom: 60px; }
        .na-card { text-align: left; position: relative; display: block; text-decoration: none; }
        .na-image-box { width: 100%; aspect-ratio: 1 / 1; background-size: cover; background-position: center top; background-color: #F5F5F5; margin-bottom: 18px; border-radius: 4px; position: relative; transition: opacity 0.3s; }
        .na-image-box:hover { opacity: 0.9; }
        .na-badge-new { position: absolute; bottom: 12px; left: 12px; background-color: #777; color: #fff; font-size: 10px; font-weight: 600; padding: 5px 10px; text-transform: uppercase; border-radius: 2px; }
        .na-prod-name { font-size: 16px; color: #333; margin-bottom: 6px; font-weight: 400; line-height: 1.4; text-decoration: none; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .na-prod-price { font-size: 16px; font-weight: 700; color: #000; }
        .na-btn-wrapper { text-align: center; }
        .na-btn { background-color: #000033; color: #fff; padding: 15px 55px; text-decoration: none; font-size: 14px; border-radius: 4px; display: inline-block; transition: background-color 0.3s; }
        .na-btn:hover { background-color: #000055; }
        
        @media (max-width: 1480px) { .na-container-dalam { max-width: 1200px !important; } .na-grid { gap: 30px; } }
        @media (max-width: 992px) { .na-grid { grid-template-columns: repeat(2, 1fr); } .na-container-dalam { max-width: 700px !important; } .na-underline { width: 180px; height: 15px; margin-top: -15px; } }
        @media (max-width: 480px) { .na-grid { grid-template-columns: 1fr; } .na-title { font-size: 32px; } .na-underline { width: 140px; height: 12px; margin-top: -12px; } }
    </style>
@endpush
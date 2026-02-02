@inject ('reviewHelper', 'Webkul\Product\Helpers\Review')
@inject ('productViewHelper', 'Webkul\Product\Helpers\View')
@inject ('productRepository', 'Webkul\Product\Repositories\ProductRepository')

@php
    // --- 1. CONFIGURASI HOTSPOT ---
    $hotspotSettings = [
        'point1' => ['id' => 64, 'top' => '10%', 'left' => '50%'],
        'point2' => ['id' => 59, 'top' => '35%', 'left' => '45%'],
        'point3' => ['id' => 54, 'top' => '50%', 'left' => '55%'],
    ];

    $getHotspotProduct = function($id) use ($productRepository) {
        $prod = ($id > 0) ? $productRepository->find($id) : null;
        if($prod) {
            return [
                'name'  => $prod->name,
                'price' => $prod->getTypeInstance()->getPriceHtml(),
                'url'   => route('shop.product_or_category.index', $prod->url_key),
                'image' => $prod->base_image_url ?? $productRepository->getBaseImage($prod)['small_image_url'],
            ];
        } else {
            return [
                'name'  => 'Contoh Produk',
                'price' => '$100.00',
                'url'   => '#',
                'image' => 'https://via.placeholder.com/150/000000/FFFFFF/?text=Product',
            ];
        }
    };

    // --- 2. DATA STANDAR ---
    $avgRatings = $reviewHelper->getAverageRating($product);
    $totalReviews = $reviewHelper->getTotalFeedback($product);
    $percentageRatings = $reviewHelper->getPercentageRating($product);
    $customAttributeValues = $productViewHelper->getAdditionalData($product);
    $attributeData = collect($customAttributeValues)->filter(fn ($item) => ! empty($item['value']));
    
    $inventory = $product->inventories->sum('qty');
    $lowStockThreshold = 50; 
    $stockPercent = ($inventory > 0) ? ($inventory / $lowStockThreshold) * 100 : 0;
    if($stockPercent > 100) $stockPercent = 100;

    $isPromoActive = false;
    $finalPromoDate = null;
    if ($product->special_price_to) $finalPromoDate = $product->special_price_to;
    elseif ($product->type == 'configurable') {
        foreach ($product->variants as $variant) {
            if ($variant->special_price_to) { $finalPromoDate = $variant->special_price_to; break; }
        }
    }
    if ($finalPromoDate && \Carbon\Carbon::parse($finalPromoDate)->isFuture()) $isPromoActive = true;

    // --- 3. AMBIL RELATED PRODUCTS ---
    $relatedProducts = $product->related_products()->take(10)->get();
    if($relatedProducts->isEmpty()){
        $relatedProducts = app('Webkul\Product\Repositories\ProductRepository')->scopeQuery(function($query) {
            return $query->inRandomOrder()->take(8);
        })->get();
    }

    // --- 4. MATERIAL TEXT ---
    $materialText = trim(strip_tags($product->short_description));
    if(empty($materialText)) {
        $materialText = "Contains sustainable materials. This product is made with at least 50% recycled polyester fibers.";
    }
@endphp

@push('meta')
    <style>
        /* === LAYOUT & STICKY IMAGE === */
        .product-detail-page .container { 
            max-width: 1650px !important; 
            padding-left: 40px; padding-right: 40px;
        }
        
        /* Container Utama Flex */
        .product-main-flex {
            display: flex;
            gap: 60px; /* Jarak antar gambar dan teks */
            align-items: flex-start; /* PENTING untuk Sticky */
        }

        /* Kolom Kiri (Gambar) - Sticky Logic */
        .product-gallery-wrapper {
            flex: 0 0 60% !important; 
            max-width: 60% !important;
            width: 60% !important;
            
            /* FITUR STICKY IMAGE */
            position: -webkit-sticky; /* Untuk Safari */
            position: sticky;
            top: 100px; /* Jarak berhenti dari atas layar saat scroll */
            align-self: flex-start;
            z-index: 5;
        }

        /* Kolom Kanan (Info) */
        .product-info-wrapper {
            flex: 0 0 40% !important;
            max-width: 40% !important;
            width: 40% !important;
            padding-top: 20px; 
        }

        /* === TIPOGRAFI BESAR (RESTORED) === */
        h1.product-title {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
            font-size: 48px !important; /* KEMBALI BESAR */
            line-height: 1.1 !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            color: #111 !important;
            margin-bottom: 10px !important;
        }

        .product-price {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
            font-size: 32px !important; /* KEMBALI BESAR */
            font-weight: 600 !important;
            color: #333 !important;
            margin-top: 15px !important;
            margin-bottom: 20px !important;
        }

        /* Thumbnail Gambar */
        .product-detail-page .swiper-slide-thumb-active { height: 160px !important; }
        .product-detail-page .swiper-slide-thumb-active img { height: 100% !important; object-fit: cover !important; }


        /* GLOBAL STYLES */
        .product-detail-page .swatch-container .swatch-option { border: 1px solid #e5e7eb; background-color: #fff; color: #000 !important; border-radius: 4px !important; min-width: 45px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 600; cursor: pointer; font-size: 14px;}
        .product-detail-page .swatch-container .color-swatch-option { width: 35px !important; height: 35px !important; border-radius: 50% !important; border: 1px solid #ddd !important;}
        .product-detail-page .swatch-container .swatch-option.active { border-color: #000 !important; }
        .product-detail-page .swatch-container .swatch-option:not(.color-swatch-option).active {background: #000 !important; color: #fff !important;}
        .product-detail-page .swatch-container .color-swatch-option.active { box-shadow: 0 0 0 2px #fff, 0 0 0 4px #000 !important; border: none !important; }

        .marketing-box { margin-bottom: 20px; }
        .countdown-timer-box { background: #fff1f2; border: 1px solid #fecdd3; color: #e11d48; padding: 10px 15px; font-weight: 500; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-radius: 4px;}
        .stock-bar-bg { width: 100%; height: 6px; background: #e5e7eb; border-radius: 3px; margin-top: 5px; }
        .stock-bar-fill { height: 100%; background: #ef4444; border-radius: 3px; }

        /* BANNER */
        .custom-promo-banner { display: flex; flex-wrap: wrap; background: #e0e0e0; margin-top: 60px; height: 90vh; min-height: 600px; position: relative; overflow: visible !important; z-index: 10; }
        .cpb-left { width: 50%; position: relative; background: #222; height: 100%; display: flex; align-items: center; justify-content: center; overflow: visible !important; }
        .cpb-right { width: 50%; padding: 60px; display: flex; flex-direction: column; justify-content: center; background: #e0e0e0; height: 100%; z-index: 5; }
        .cpb-img { width: 100%; height: 100%; object-fit: cover; object-position: center center; }

        /* HOTSPOTS */
        .hotspot { position: absolute; z-index: 100; cursor: pointer; }
        .hotspot-dot { width: 24px; height: 24px; background: rgba(255, 255, 255, 0.4); border-radius: 50%; border: 2px solid #fff; position: relative; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .hotspot-dot::after { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 10px; height: 10px; background: #fff; border-radius: 50%; }
        .hotspot:hover .hotspot-dot, .hotspot.active .hotspot-dot { background: #fff; transform: scale(1.2); }
        .hotspot:hover .hotspot-dot::after, .hotspot.active .hotspot-dot::after { background: #000; }
        .hotspot-card { position: absolute; left: 40px; top: 50%; transform: translateY(-50%); background: #fff; padding: 12px; width: 240px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); display: flex; gap: 12px; align-items: center; opacity: 0; visibility: hidden; transition: all 0.2s ease; pointer-events: none; z-index: 101; }
        .hotspot.active .hotspot-card { opacity: 1; visibility: visible; pointer-events: auto; left: 50px; }
        .hotspot-card::before { content: ''; position: absolute; left: -6px; top: 50%; transform: translateY(-50%); width: 0; height: 0; border-top: 6px solid transparent; border-bottom: 6px solid transparent; border-right: 6px solid #fff; }
        .hc-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; background: #f3f3f3; flex-shrink: 0;}
        .hc-info { display: flex; flex-direction: column; justify-content: center; width: 100%;}
        .hc-name { font-size: 13px; font-weight: bold; color: #000; margin-bottom: 3px; line-height: 1.2; }
        .hc-price { font-size: 12px; color: #666; margin-bottom: 5px; }
        .hc-link { font-size: 11px; text-transform: uppercase; font-weight: bold; color: #000; text-decoration: underline; }

        /* CAROUSEL */
        .interested-section { padding: 60px 20px; background: #fff; border-bottom: 1px solid #eee; }
        .is-header { font-family: Serif; font-size: 32px; margin-bottom: 30px; text-align: left; font-weight: bold; color: #000; }
        .is-carousel-wrapper { position: relative; width: 100%; }
        .is-list { display: flex; gap: 25px; overflow-x: auto; scroll-behavior: smooth; padding-bottom: 20px; scrollbar-width: thin; scrollbar-color: #000 #e5e5e5; }
        .is-list::-webkit-scrollbar { height: 6px; }
        .is-list::-webkit-scrollbar-track { background: #e5e5e5; border-radius: 3px; }
        .is-list::-webkit-scrollbar-thumb { background-color: #000; border-radius: 3px; cursor: pointer; }
        .is-nav { position: absolute; top: 40%; transform: translateY(-50%); width: 45px; height: 45px; background: #fff; border-radius: 50%; box-shadow: 0 4px 15px rgba(0,0,0,0.2); border: 1px solid #eee; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 50; transition: all 0.3s; font-size: 20px; color: #000; }
        .is-nav:hover { background: #000; color: #fff; border-color: #000; }
        .is-prev { left: 10px; }
        .is-next { right: 10px; }
        .is-card { width: 300px; min-width: 300px; flex-shrink: 0; display: flex; flex-direction: column; }
        .is-img-wrapper { width: 100%; height: 380px; background: #f4f4f4; overflow: hidden; margin-bottom: 15px; position: relative; }
        .is-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .is-card:hover .is-img { transform: scale(1.05); }
        .is-info { text-align: left; }
        .is-name { font-size: 16px; font-weight: 600; margin-bottom: 5px; color: #000; text-decoration: none; display: block;}
        .is-price { font-size: 14px; color: #555; margin-bottom: 12px; }
        .is-colors { display: flex; gap: 8px; margin-top: 5px; min-height: 20px;}
        .is-color-dot { width: 20px; height: 20px; border-radius: 50%; border: 1px solid #ddd; cursor: pointer; position: relative; background-size: cover; }
        .is-color-dot.active { border: 1px solid #000; }

        /* FASHION PERFORMANCE SECTION */
        .fp-section { padding: 90px 20px; background: #fff; text-align: center; border-bottom: 1px solid #eee; }
        .fp-container { max-width: 1200px; margin: 0 auto; }
        .fp-header-block { margin-bottom: 60px; }
        .fp-title { font-family: 'Times New Roman', Times, serif; font-size: 42px; font-weight: bold; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px; color: #000; }
        .fp-subtitle { font-family: Arial, sans-serif; font-size: 18px; color: #666; max-width: 800px; margin: 0 auto; line-height: 1.6; font-weight: 300; }
        .fp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; }
        .fp-card { text-align: left; }
        .fp-img-wrap { width: 100%; height: 500px; overflow: hidden; margin-bottom: 25px; background: #f4f4f4; position: relative; }
        .fp-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .fp-card:hover .fp-img { transform: scale(1.03); }
        .fp-content h3 { font-size: 22px; font-weight: bold; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #000; font-family: 'Times New Roman', Times, serif; }
        .fp-content p { font-size: 15px; color: #555; line-height: 1.7; font-family: Arial, sans-serif; }

        /* MODAL SIZE GUIDE */
        .sg-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(5px); align-items: center; justify-content: center; }
        .sg-modal.show { display: flex; animation: fadeIn 0.3s; }
        .sg-content { background-color: #fff; width: 90%; max-width: 900px; border-radius: 8px; overflow: hidden; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.3); display: flex; flex-direction: column; animation: slideUp 0.3s; max-height: 90vh; overflow-y: auto; }
        .sg-header { padding: 20px 30px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .sg-title { font-size: 24px; font-weight: bold; font-family: Serif; }
        .sg-close { font-size: 28px; cursor: pointer; color: #999; transition: color 0.2s;}
        .sg-close:hover { color: #000; }
        .sg-body { padding: 30px; display: flex; gap: 40px; }
        .sg-left { flex: 1; }
        .sg-right { width: 300px; text-align: center; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .sg-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .sg-table th { text-align: left; padding: 12px; border-bottom: 2px solid #000; font-weight: bold; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;}
        .sg-table td { padding: 12px; border-bottom: 1px solid #eee; color: #555; }
        .sg-table tr:hover td { background: #f8f8f8; color: #000; }
        .sg-diagram { width: 100%; max-width: 200px; margin-bottom: 15px; mix-blend-mode: multiply; }
        .sg-helper-text { font-size: 13px; color: #666; line-height: 1.5; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* ACCORDION */
        .p-accordion { border-top: 1px solid #e5e5e5; margin-top: 40px; }
        .pa-item { border-bottom: 1px solid #e5e5e5; }
        .pa-header { width: 100%; padding: 25px 0; display: flex; justify-content: space-between; align-items: center; background: none; border: none; cursor: pointer; text-align: left; transition: color 0.2s; }
        .pa-header:hover { opacity: 0.7; }
        .pa-title { font-size: 18px; font-weight: 600; color: #111; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .pa-icon { transition: transform 0.3s ease; font-size: 14px; font-weight: bold;}
        .pa-item.active .pa-icon { transform: rotate(180deg); }
        .pa-content { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .pa-body { padding-bottom: 25px; font-size: 15px; line-height: 1.6; color: #757575; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .pa-body p { margin-bottom: 10px; }
        
        /* PAYMENT ICONS (CENTERED BOX) */
        .payment-trust-badge {
            margin-top: 30px; margin-bottom: 20px; background-color: #f8f8f8; border: 1px solid #e5e5e5;
            border-radius: 8px; padding: 20px; text-align: center;
        }
        .ptb-icons { display: flex; gap: 15px; align-items: center; justify-content: center; flex-wrap: wrap; margin-bottom: 8px; }
        .ptb-icons img { height: 28px; width: auto; object-fit: contain; opacity: 0.9; }
        .payment-label { font-size: 12px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; color: #555; display: block; margin-top: 5px; }

        /* REVIEWS SECTION (BOTTOM) */
        .reviews-only-section { padding: 60px 20px; max-width: 1600px; margin: 0 auto; }
        .reviews-header { font-family: 'Times New Roman', Times, serif; font-size: 36px; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 15px; margin-bottom: 40px; letter-spacing: 1px;}

        @media (max-width: 900px) {
            .product-detail-page .container { padding-left: 20px; padding-right: 20px; }
            h1.product-title { font-size: 28px !important; }
            .product-price { font-size: 24px !important; }
            .product-info-wrapper { flex: 0 0 100% !important; max-width: 100% !important; width: 100% !important; padding-left: 0 !important; }
            .product-detail-page .flex.gap-9 > div:first-child { flex: 0 0 100% !important; max-width: 100% !important; width: 100% !important; }
            .product-detail-page .swiper-slide-thumb-active { height: 90px !important; }
            .custom-promo-banner { flex-direction: column; height: auto; min-height: auto; }
            .cpb-left { width: 100%; height: 500px; }
            .cpb-right { width: 100%; height: auto; padding: 40px; }
            .hotspot-card { left: 50% !important; top: 50px !important; transform: translateX(-50%) !important; }
            .is-card { width: 220px; min-width: 220px; }
            .is-img-wrapper { height: 280px; }
            .is-nav { display: none; }
            .fp-grid { grid-template-columns: 1fr; gap: 50px; }
            .fp-title { font-size: 28px; }
            .fp-img-wrap { height: 400px; }
            .sg-body { flex-direction: column-reverse; padding: 20px; }
            .sg-right { width: 100%; }
            .sg-table { font-size: 12px; }
        }
    </style>

    <meta name="description" content="{{ trim($product->meta_description) != "" ? $product->meta_description : \Illuminate\Support\Str::limit(strip_tags($product->description), 120, '') }}"/>
    @if (core()->getConfigData('catalog.rich_snippets.products.enable'))
        <script type="application/ld+json"> {!! app('Webkul\Product\Helpers\SEO')->getProductJsonLd($product) !!} </script>
    @endif
    <?php $productBaseImage = product_image()->getProductBaseImage($product); ?>
    <meta property="og:image" content="{{ $productBaseImage['medium_image_url'] }}" />
@endPush

<x-shop::layouts>
    <x-slot:title>{{ trim($product->meta_title) != "" ? $product->meta_title : $product->name }}</x-slot>
    {!! view_render_event('bagisto.shop.products.view.before', ['product' => $product]) !!}

    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        <div class="flex justify-center px-7 max-lg:hidden">
            <x-shop::breadcrumbs name="product" :entity="$product" />
        </div>
    @endif

    <v-product>
        <x-shop::shimmer.products.view />
    </v-product>

        <div class="container-fluid" style="max-width: 100%; overflow-x: hidden;">

        {{-- BANNER HOTSPOT --}}
        <div class="custom-promo-banner">
            <div class="cpb-left">
                <img src="https://ik.imagekit.io/p16mdchf9/upscalemedia-transformed-removebg-preview%20(1).jpg" class="cpb-img" id="main-banner-img">
                @php $p1 = $getHotspotProduct($hotspotSettings['point1']['id']); @endphp
                <div class="hotspot" onclick="this.classList.toggle('active')" style="top: {{ $hotspotSettings['point1']['top'] }}; left: {{ $hotspotSettings['point1']['left'] }};"><div class="hotspot-dot"></div><div class="hotspot-card"><img src="{{ $p1['image'] }}" class="hc-thumb"><div class="hc-info"><div class="hc-name">{{ $p1['name'] }}</div><div class="hc-price">{!! $p1['price'] !!}</div><a href="{{ $p1['url'] }}" class="hc-link">View Product →</a></div></div></div>
                @php $p2 = $getHotspotProduct($hotspotSettings['point2']['id']); @endphp
                <div class="hotspot" onclick="this.classList.toggle('active')" style="top: {{ $hotspotSettings['point2']['top'] }}; left: {{ $hotspotSettings['point2']['left'] }};"><div class="hotspot-dot"></div><div class="hotspot-card"><img src="{{ $p2['image'] }}" class="hc-thumb"><div class="hc-info"><div class="hc-name">{{ $p2['name'] }}</div><div class="hc-price">{!! $p2['price'] !!}</div><a href="{{ $p2['url'] }}" class="hc-link">View Product →</a></div></div></div>
                @php $p3 = $getHotspotProduct($hotspotSettings['point3']['id']); @endphp
                <div class="hotspot" onclick="this.classList.toggle('active')" style="top: {{ $hotspotSettings['point3']['top'] }}; left: {{ $hotspotSettings['point3']['left'] }};"><div class="hotspot-dot"></div><div class="hotspot-card"><img src="{{ $p3['image'] }}" class="hc-thumb"><div class="hc-info"><div class="hc-name">{{ $p3['name'] }}</div><div class="hc-price">{!! $p3['price'] !!}</div><a href="{{ $p3['url'] }}" class="hc-link">View Product →</a></div></div></div>
            </div>
            <div class="cpb-right">
                <span style="text-transform: uppercase; letter-spacing: 2px; color: #555; font-size: 14px; margin-bottom: 10px;">Collection</span>
                <h2 style="font-family: Arial, sans-serif; font-size: 52px; margin-bottom: 25px; line-height: 1.1; font-weight: bold; color: #333;">{{ $product->name }}</h2>
                <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 10px; color: #333;">Description</h3>
                <div style="color: #666; line-height: 1.8; margin-bottom: 35px; font-size: 15px; max-width: 550px;">
                    {!! \Illuminate\Support\Str::limit(strip_tags($product->description), 400) !!}
                </div>
                <div style="font-size: 36px; font-weight: bold; margin-bottom: 35px; color: #333;">{!! $product->getTypeInstance()->getPriceHtml() !!}</div>
                <a href="#" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;" style="display: inline-block; background: #000; color: #fff; padding: 18px 50px; text-transform: uppercase; font-weight: bold; border-radius: 4px; width: fit-content; font-size: 14px; letter-spacing: 1px;">Buy Now</a>
            </div>
        </div>

        {{-- FASHION PERFORMANCE SECTION (PINDAH KE ATAS) --}}
        <div class="fp-section">
            <div class="fp-container">
                <div class="fp-header-block">
                    <h2 class="fp-title">Move Smarter on the Padel Court</h2>
                    <p class="fp-subtitle">Kami menggabungkan siluet yang berfokus pada performa dengan bahan padel premium, menciptakan pakaian yang terlihat tajam dan bergerak dengan lancar pada setiap pukulan.</p>
                </div>
                <div class="fp-grid">
                    <div class="fp-card">
                        <div class="fp-img-wrap"><img src="https://ik.imagekit.io/p16mdchf9/padel-optimized-design.png" class="fp-img" alt="Movement"></div>
                        <div class="fp-content"><h3>Padel-Optimized Design</h3><p>Lebih dari 45+ paten kami adaptasikan untuk kebutuhan padel. Setiap potongan dirancang untuk mendukung rotasi, kecepatan ayunan, dan kelincahan, sehingga gerakanmu tetap bebas dan tampilan tetap clean.</p></div>
                    </div>
                    <div class="fp-card">
                        <div class="fp-img-wrap"><img src="https://ik.imagekit.io/p16mdchf9/high-performance-material.png" class="fp-img" alt="Luxury Fabric"></div>
                        <div class="fp-content"><h3>High-Performance Materials</h3><p>Material premium kami ringan, breathable, dan cepat kering siap menghadapi rally panjang di lapangan indoor maupun outdoor sambil menjaga kenyamanan tubuh.</p></div>
                    </div>
                    <div class="fp-card">
                        <div class="fp-img-wrap"><img src="https://ik.imagekit.io/p16mdchf9/player-friendly-features.png" class="fp-img" alt="Clever Cuts"></div>
                        <div class="fp-content"><h3>Player-Friendly Features</h3><p>Setiap rok dan celana dilengkapi kantong sisi ganda yang mudah dijangkau, ergonomis, dan nyaman untuk pemain kidal maupun non-kidal—praktis tanpa mengorbankan gaya.</p></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- YOU MAYBE INTERESTED IN (PINDAH KE BAWAH) --}}
        <div class="interested-section">
            <div class="container mx-auto max-w-[1200px]">
                <h2 class="is-header">You Maybe Interested in</h2>
                <div class="is-carousel-wrapper">
                    <div class="is-nav is-prev" onclick="window.scrollInterested('left')"><span class="icon-arrow-left"></span></div>
                    <div class="is-list" id="interestedList">
                        @foreach($relatedProducts as $related)
                            @php 
                                $relImage = product_image()->getProductBaseImage($related); 
                                $relColors = [];
                                if ($related->type == 'configurable') {
                                    foreach ($related->super_attributes as $attr) {
                                        if ($attr->code == 'color') {
                                            foreach ($attr->options as $opt) { $relColors[] = $opt->swatch_value ?? null; }
                                        }
                                    }
                                }
                                $relColors = array_slice(array_unique(array_filter($relColors)), 0, 4);
                            @endphp
                            <div class="is-card">
                                <a href="{{ route('shop.product_or_category.index', $related->url_key) }}" class="is-img-wrapper">
                                    <img src="{{ $relImage['medium_image_url'] }}" class="is-img" alt="{{ $related->name }}">
                                </a>
                                <div class="is-info">
                                    <a href="{{ route('shop.product_or_category.index', $related->url_key) }}" class="is-name">{{ $related->name }}</a>
                                    <div class="is-price">{!! $related->getTypeInstance()->getPriceHtml() !!}</div>
                                    @if(count($relColors) > 0)
                                        <div class="is-colors">
                                            @foreach($relColors as $colorVal)
                                                @if(str_contains($colorVal, '#'))
                                                    <div class="is-color-dot" style="background-color: {{ $colorVal }};"></div>
                                                @else
                                                    <div class="is-color-dot" style="background-image: url('{{ Storage::url($colorVal) }}');"></div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="is-nav is-next" onclick="window.scrollInterested('right')"><span class="icon-arrow-right"></span></div>
                </div>
            </div>
        </div>

    </div>

    <v-product-associations />
    {!! view_render_event('bagisto.shop.products.view.after', ['product' => $product]) !!}

    {{-- MODAL SIZE GUIDE (DI LUAR VUE APP) --}}
    <div id="sizeGuideModal" class="sg-modal" onclick="if(event.target === this) closeSizeGuide()">
        <div class="sg-content">
            <div class="sg-header">
                <span class="sg-title">Size Guide</span>
                <span class="sg-close" onclick="closeSizeGuide()">&times;</span>
            </div>
            <div class="sg-body">
                <div class="sg-left">
                    <table class="sg-table">
                        <thead><tr><th>Size</th><th>Chest (cm)</th><th>Waist (cm)</th><th>Length (cm)</th></tr></thead>
                        <tbody>
                            <tr><td><strong>S</strong></td><td>92 - 96</td><td>76 - 80</td><td>68</td></tr>
                            <tr><td><strong>M</strong></td><td>96 - 100</td><td>80 - 84</td><td>70</td></tr>
                            <tr><td><strong>L</strong></td><td>100 - 104</td><td>84 - 88</td><td>72</td></tr>
                            <tr><td><strong>XL</strong></td><td>104 - 110</td><td>88 - 94</td><td>74</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="sg-right">
                    <img src="https://cdn-icons-png.flaticon.com/512/863/863684.png" class="sg-diagram">
                    <p class="sg-helper-text">Measure around the fullest part of your chest and waist. Keep the tape loose.</p>
                </div>
            </div>
        </div>
    </div>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-product-template">
            <x-shop::form v-slot="{ meta, errors, handleSubmit }" as="div">
                <form ref="formData" @submit="handleSubmit($event, addToCart)">
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="is_buy_now" v-model="is_buy_now">
                    <div class="container px-[60px] max-1180:px-0">
                        <div class="mt-12 flex gap-9 max-1180:flex-wrap max-lg:mt-0 max-sm:gap-y-4">
                            <div class="max-w-[590px] max-1180:w-full max-1180:max-w-full max-sm:px-[20px]">@include('shop::products.view.gallery')</div>
                            <div class="relative product-info-wrapper max-w-[590px] max-1180:w-full max-1180:max-w-full max-1180:px-5 max-sm:px-4">
                                {!! view_render_event('bagisto.shop.products.name.before', ['product' => $product]) !!}
                                <div class="flex justify-between gap-4">
                                    {{-- LOGO DINAMIS DARI ADMIN PANEL --}}
                                    <div class="w-full">
                                        @if($logo = core()->getCurrentChannel()->logo_url)
                                            <img src="{{ $logo }}" alt="{{ core()->getCurrentChannel()->name }}" class="product-brand-logo">
                                        @else
                                            <span class="brand-logo-text">{{ core()->getCurrentChannel()->name }}</span>
                                        @endif
                                    </div>
                                    @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))<div class="flex max-h-[46px] min-h-[46px] min-w-[46px] cursor-pointer items-center justify-center rounded-full border bg-white text-2xl transition-all hover:opacity-[0.8]" role="button" :class="isWishlist ? 'icon-heart-fill text-red-600' : 'icon-heart'" @click="addToWishlist"></div>@endif
                                </div>
                                {!! view_render_event('bagisto.shop.products.name.after', ['product' => $product]) !!}
                                
                                <h1 class="break-words text-3xl font-medium max-sm:text-xl product-title">{{ $product->name }}</h1>
                                
                                {{-- STAR RATING (SCROLL TO BOTTOM REVIEWS) --}}
                                <div class="custom-star-rating" onclick="document.getElementById('review-section').scrollIntoView({behavior: 'smooth'})">
                                    @for($i = 1; $i <= 5; $i++)<span class="{{ $i <= round($avgRatings) ? 'icon-star-fill' : 'icon-star' }}"></span>@endfor
                                    <span class="review-count-text">({{ $totalReviews }} Reviews)</span>
                                </div>

                                <p class="mt-[15px] flex items-center gap-2.5 text-2xl !font-medium product-price">{!! $product->getTypeInstance()->getPriceHtml() !!}</p>

                                <div class="marketing-box mt-4">
                                    <div class="flex items-center gap-2 text-zinc-500 text-sm mb-3"><span class="icon-eye text-lg"></span> <span><strong id="view-count">24</strong> people are viewing this right now</span></div>
                                    @if ($isPromoActive)<div class="countdown-timer-box rounded"><span>Hurry up! Sale ends in:</span><span id="standard-timer" class="text-lg font-bold">Loading...</span></div>@endif
                                    @if ($product->manage_stock > 0 && $inventory > 0 && $inventory < $lowStockThreshold)
                                        <div class="mb-4">
                                            <p class="text-sm text-zinc-600 mb-1">Only <strong class="text-red-600">{{ $inventory }} item(s)</strong> left in stock!</p>
                                            <div class="stock-bar-bg">
                                                <div class="stock-bar-fill" style="width: {{ $stockPercent }}%">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @include('shop::products.view.types.configurable')
                                @include('shop::products.view.types.simple')
                                @include('shop::products.view.types.grouped')
                                @include('shop::products.view.types.bundle')
                                @include('shop::products.view.types.downloadable')
                                @include('shop::products.view.types.booking')
                                <div class="mt-8 max-w-[470px] max-sm:mt-4">
                                    <div class="mb-2 text-left">
                                        <button type="button" class="text-sm font-bold cursor-pointer hover:text-black text-gray-600 flex items-center gap-1" onclick="openSizeGuide()">
                                            <span style="font-size: 18px;"></span> Panduan Ukuran
                                        </button>
                                    </div>
                                    <p class="mb-2 font-medium text-gray-700 label-qty">Quantity</p>
                                    <div class="flex gap-4">
                                        <x-shop::quantity-changer name="quantity" value="1" class="gap-x-4 rounded-xl px-7 py-4" />
                                        <x-shop::button type="submit" class="secondary-button w-full !bg-black !text-white !font-bold" :title="trans('shop::app.products.view.add-to-cart')" :disabled="! $product->isSaleable(1)" ::loading="isStoring.addToCart" @click="is_buy_now=0;" />
                                    </div>
                                </div>

                                {{-- ACCORDION (TANPA REVIEWS) --}}
                                <div class="p-accordion">
                                    <div class="pa-item">
                                        <button type="button" class="pa-header" onclick="toggleAccordion(this)">
                                            <span class="pa-title">Description</span>
                                            <span class="icon-arrow-down pa-icon"></span>
                                        </button>
                                        <div class="pa-content"><div class="pa-body">{!! $product->description !!}</div></div>
                                    </div>
                                    <div class="pa-item">
                                        <button type="button" class="pa-header" onclick="toggleAccordion(this)">
                                            <span class="pa-title">Materials</span>
                                            <span class="icon-arrow-down pa-icon"></span>
                                        </button>
                                        <div class="pa-content"><div class="pa-body">{!! nl2br(e($materialText)) !!}</div></div>
                                    </div>
                                    <div class="pa-item">
                                        <button type="button" class="pa-header" onclick="toggleAccordion(this)">
                                            <span class="pa-title">Free Shipping & Returns</span>
                                            <span class="icon-arrow-down pa-icon"></span>
                                        </button>
                                        <div class="pa-content"><div class="pa-body"><p>Your order of Rp 3,000,000 or more gets free standard delivery.</p><p><strong>Standard delivery</strong> 6–12 Working Days<br><strong>Express delivery</strong> 3–10 Working Days</p><p>During checkout, we'll provide you with the estimated delivery date based on your order's delivery address. Orders are processed and delivered Monday–Friday (excluding public holidays).</p><p>Nike Members enjoy free returns. Exclusions Apply.</p></div></div>
                                    </div>
                                </div>

                                {{-- PAYMENT TRUST BADGE (CENTERED) --}}
                                <div class="payment-trust-badge">
                                    <div class="ptb-icons">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" alt="QRIS" title="QRIS">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" alt="BCA" title="BCA">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f0/Bank_Negara_Indonesia_logo_%282004%29.svg/2560px-Bank_Negara_Indonesia_logo_%282004%29.svg.png" alt="BNI" title="BNI">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg" alt="BRI" title="BRI">
                                    </div>
                                    <span class="payment-label">Guarantee safe & secure checkout</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-12 border-t border-gray-200 pt-10" id="review-section" style="width: 100%;">
                        <h2 class="text-3xl font-bold mb-8 text-center" style="font-family: 'Times New Roman', serif; text-transform: uppercase;">
                            Customer Reviews
                        </h2>
                        
                        {{-- Panggil Review Bagisto --}}
                        @include('shop::products.view.reviews')
                    </div>
                    </div>
                </form>
            </x-shop::form>
        </script>

        <script type="module">
            app.component('v-product', {
                template: '#v-product-template',
                data() { return { isWishlist: Boolean("{{ (boolean) auth()->guard()->user()?->wishlist_items->where('channel_id', core()->getCurrentChannel()->id)->where('product_id', $product->id)->count() }}"), isCustomer: '{{ auth()->guard('customer')->check() }}', is_buy_now: 0, isStoring: { addToCart: false, buyNow: false }, } },
                methods: {
                    addToCart(params) { if (this.isCustomer) { const operation = this.is_buy_now ? 'buyNow' : 'addToCart'; this.isStoring[operation] = true; let formData = new FormData(this.$refs.formData); this.ensureQuantity(formData); this.$axios.post('{{ route("shop.api.checkout.cart.store") }}', formData, { headers: { 'Content-Type': 'multipart/form-data' } }).then(response => { if (response.data.message) { this.$emitter.emit('update-mini-cart', response.data.data); this.$emitter.emit('add-flash', { type: 'success', message: response.data.message }); if (response.data.redirect) window.location.href= response.data.redirect; } else { this.$emitter.emit('add-flash', { type: 'warning', message: response.data.data.message }); } this.isStoring[operation] = false; }).catch(error => { this.isStoring[operation] = false; this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.message }); }); } else { let url = new URL(`${window.location.origin}/customer/login`); window.location.href = url.href; } },
                    addToWishlist() { if (this.isCustomer) { this.$axios.post('{{ route('shop.api.customers.account.wishlist.store') }}', { product_id: "{{ $product->id }}" }).then(response => { this.isWishlist = ! this.isWishlist; this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message }); }).catch(error => {}); } else { window.location.href = "{{ route('shop.customer.session.index')}}"; } },
                    addToCompare(productId) { this.$axios.post('{{ route("shop.api.compare.store") }}', { product_id: productId }).then(response => { this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message }); }).catch(error => { this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message }); }); },
                    ensureQuantity(formData) { if (! formData.has('quantity')) formData.append('quantity', 1); },
                },
            });
        </script>
        
        <script>
            window.scrollInterested = function(direction) {
                const list = document.getElementById('interestedList');
                if(list) {
                    const scrollAmount = 350; 
                    if(direction === 'right') { list.scrollBy({ left: scrollAmount, behavior: 'smooth' }); } else { list.scrollBy({ left: -scrollAmount, behavior: 'smooth' }); }
                }
            };

            window.openSizeGuide = function() {
                const modal = document.getElementById('sizeGuideModal');
                if(modal) {
                    modal.style.display = 'flex';
                    setTimeout(() => modal.classList.add('show'), 10);
                }
            };

            window.closeSizeGuide = function() {
                const modal = document.getElementById('sizeGuideModal');
                if(modal) {
                    modal.classList.remove('show');
                    setTimeout(() => modal.style.display = 'none', 300);
                }
            };

            window.toggleAccordion = function(element) {
                const item = element.parentElement;
                const content = item.querySelector('.pa-content');
                item.classList.toggle('active');
                if (content.style.maxHeight) {
                    content.style.maxHeight = null;
                } else {
                    content.style.maxHeight = content.scrollHeight + "px";
                }
            };

            document.addEventListener('DOMContentLoaded', function() {
                @if ($isPromoActive && $finalPromoDate)
                    var countDownDateStd = new Date("{{ $finalPromoDate }}").getTime();
                    var xStd = setInterval(function() { var now = new Date().getTime(); var distance = countDownDateStd - now; var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)); var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)); var seconds = Math.floor((distance % (1000 * 60)) / 1000); hours = hours < 10 ? "0" + hours : hours; minutes = minutes < 10 ? "0" + minutes : minutes; seconds = seconds < 10 ? "0" + seconds : seconds; let timerElemStd = document.getElementById("standard-timer"); if(timerElemStd) timerElemStd.innerHTML = hours + " : " + minutes + " : " + seconds; if (distance < 0) { clearInterval(xStd); if(timerElemStd) timerElemStd.innerHTML = "EXPIRED"; } }, 1000);
                @endif
                const hotspots = document.querySelectorAll('.hotspot');
                hotspots.forEach(hotspot => { hotspot.addEventListener('click', function(e) { e.stopPropagation(); hotspots.forEach(h => { if (h !== this) h.classList.remove('active'); }); this.classList.toggle('active'); }); });
                document.addEventListener('click', function() { hotspots.forEach(h => h.classList.remove('active')); });
            });
        </script>
    @endPushOnce
</x-shop::layouts>
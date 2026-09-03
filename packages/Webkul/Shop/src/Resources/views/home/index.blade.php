{{-- packages/Webkul/Shop/src/Resources/views/home/index.blade.php --}}
@inject('productFlatRepository', 'Webkul\Product\Repositories\ProductFlatRepository')

@php
    $channel = core()->getCurrentChannel();
    $locale = core()->getCurrentLocale();

    $newArrivals = $productFlatRepository->scopeQuery(function ($query) use ($channel, $locale) {
        return $query->where('status', 1)
                     ->where('visible_individually', 1)
                     ->where('channel', $channel->code)
                     ->where('locale', $locale->code)
                     ->orderBy('created_at', 'desc');
    })->paginate(8);
@endphp

@push('meta')
    <meta name="title" content="{{ $channel->home_seo['meta_title'] ?? '' }}" />
    <meta name="description" content="{{ $channel->home_seo['meta_description'] ?? '' }}" />
    <meta name="keywords" content="{{ $channel->home_seo['meta_keywords'] ?? '' }}" />
@endpush

<x-shop::layouts>
    <x-slot:title>{{ $channel->home_seo['meta_title'] ?? '' }}</x-slot>

    @foreach ($customizations as $customization)
        @php $data = $customization->options; @endphp

        @switch($customization->type)
            @case($customization::IMAGE_CAROUSEL)
                <x-shop::carousel :options="$data" aria-label="Image Carousel" />
                @break

            @case($customization::STATIC_CONTENT)
                @if (! empty($data['css']))
                    @push('styles')
                        <style>{{ $data['css'] }}</style>
                    @endpush
                @endif

                @if (! empty($data['html']))
                    @if (Str::contains($data['html'], 'na-wrapper-luar'))
                        @if ($newArrivals->count() > 0)
                            <div class="na-wrapper-luar">
                                <div class="na-container-dalam">
                                    <div class="na-header">
                                        <h2 class="na-title">New Arrivals</h2>
                                        <div class="na-underline"></div>
                                    </div>

                                    <div class="na-grid">
                                        @foreach ($newArrivals as $item)
                                            @php
                                                // Pastikan ambil Product model (bukan hanya flat)
                                                $product = $item->product ?? $item;
                                                $image = product_image()->getProductBaseImage($product);
                                                $imageUrl = $image['medium_image_url'] ?? $image['small_image_url'] ?? '';
                                            @endphp

                                            <div class="na-card">
                                                <a href="{{ route('shop.product_or_category.index', $product->url_key) }}">
                                                    <div class="na-image-box" style="background-image: url('{{ $imageUrl }}');">
                                                        <span class="na-badge-new">New Arrival</span>
                                                    </div>
                                                </a>

                                                <a href="{{ route('shop.product_or_category.index', $product->url_key) }}" class="na-prod-name">
                                                    {{ $product->name }}
                                                </a>

                                                <div class="na-prod-price">
                                                    {!! $product->getTypeInstance()->getPriceHtml() !!}
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
                        @if (Str::contains($data['html'], 'public/storage'))
                            {!! str_replace('public/storage', 'storage', $data['html']) !!}
                        @else
                            {!! $data['html'] !!}
                        @endif
                    @endif
                @endif
                @break

            @case($customization::CATEGORY_CAROUSEL)
            <div id="koleksi-kategori" style="scroll-margin-top: 100px;">
                <x-shop::categories.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.categories.index', $data['filters'] ?? [])"
                    :navigation-link="route('shop.home.index')"
                    aria-label="Category Carousel"
                />
                </div>
                @break
                @case($customization::PRODUCT_CAROUSEL)
                <x-shop::products.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.products.index', $data['filters'] ?? [])"
                    :navigation-link="route('shop.search.index', $data['filters'] ?? [])"
                />
                @break
        @endswitch
    @endforeach

</x-shop::layouts>

@push('styles')
    <style>
        /* minimal CSS — copy your full styles here if perlu */
        .na-wrapper-luar { width: 100%; display: flex; justify-content: center; background-color: #fff; padding: 60px 0; }
        .na-container-dalam { width: 100%; max-width: 1440px; padding: 0 20px; box-sizing: border-box; }
        .na-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 30px; }
        .na-image-box { width: 100%; aspect-ratio: 1/1; background-size: cover; background-position: center; background-color: #f5f5f5; }
    </style>
@endpush

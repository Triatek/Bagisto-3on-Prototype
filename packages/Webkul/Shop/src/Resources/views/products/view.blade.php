@inject ('reviewHelper', 'Webkul\Product\Helpers\Review')
@inject ('productViewHelper', 'Webkul\Product\Helpers\View')

@php
    $avgRatings = $reviewHelper->getAverageRating($product);
    $percentageRatings = $reviewHelper->getPercentageRating($product);
    $customAttributeValues = $productViewHelper->getAdditionalData($product);
    $attributeData = collect($customAttributeValues)->filter(fn ($item) => ! empty($item['value']));
    
    // AMBIL DATA ATRIBUT SPESIAL KITA
    // Pastikan 'family_name' sesuai dengan nama Family yang anda buat di admin (case sensitive)
    $isSpecialFamily = $product->attribute_family->name == 'Special Campaign Product'; 
    // Atau bisa cek manual jika anda membuat atribut dropdown 'use_special_layout'
    // $isSpecialLayout = $product->getAttribute('use_special_layout') == 'Yes';
@endphp

@push('meta')
    <meta name="description" content="{{ trim($product->meta_description) != "" ? $product->meta_description : \Illuminate\Support\Str::limit(strip_tags($product->description), 120, '') }}"/>
    <meta name="keywords" content="{{ $product->meta_keywords }}"/>
    @if (core()->getConfigData('catalog.rich_snippets.products.enable'))
        <script type="application/ld+json">
            {!! app('Webkul\Product\Helpers\SEO')->getProductJsonLd($product) !!}
        </script>
    @endif
    <?php $productBaseImage = product_image()->getProductBaseImage($product); ?>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $product->name }}" />
    <meta name="twitter:description" content="{!! htmlspecialchars(trim(strip_tags($product->description))) !!}" />
    <meta name="twitter:image:alt" content="" />
    <meta name="twitter:image" content="{{ $productBaseImage['medium_image_url'] }}" />
    <meta property="og:type" content="og:product" />
    <meta property="og:title" content="{{ $product->name }}" />
    <meta property="og:image" content="{{ $productBaseImage['medium_image_url'] }}" />
    <meta property="og:description" content="{!! htmlspecialchars(trim(strip_tags($product->description))) !!}" />
    <meta property="og:url" content="{{ route('shop.product_or_category.index', $product->url_key) }}" />
@endPush

<x-shop::layouts>
    <x-slot:title>
        {{ trim($product->meta_title) != "" ? $product->meta_title : $product->name }}
    </x-slot>

    {!! view_render_event('bagisto.shop.products.view.before', ['product' => $product]) !!}

    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        <div class="flex justify-center px-7 max-lg:hidden">
            <x-shop::breadcrumbs name="product" :entity="$product" />
        </div>
    @endif

    {{-- ================================================================= --}}
    {{-- LOGIC SWITCH: Layout Spesial vs Layout Standar --}}
    {{-- ================================================================= --}}

    @if($isSpecialFamily)
    
        {{-- ==================== TAMPILAN SPESIAL (NEW) ==================== --}}
        
        <div class="container mt-10 px-[60px] max-1180:px-5">
            
            <div class="flex gap-10 max-md:flex-wrap">
                <div class="w-1/2 max-md:w-full">
                     @include('shop::products.view.gallery')
                </div>

                <div class="w-1/2 max-md:w-full">
                    <h5 class="text-blue-600 font-bold text-sm mb-2">SPECIAL OFFER</h5>
                    <h1 class="text-4xl font-bold mb-4">{{ $product->name }}</h1>

                    <div class="flex items-center mb-4">
                        <x-shop::products.ratings :average="$avgRatings" :total="$reviewHelper->getTotalFeedback($product)" />
                    </div>

                    <div class="text-3xl font-bold text-red-600 mb-6">
                        {!! $product->getTypeInstance()->getPriceHtml() !!}
                    </div>

                    @if($product->getAttribute('special_end_date'))
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                            <p class="font-bold text-sm uppercase">Hurry Up! Offer Ends In:</p>
                            <div class="special-countdown-timer text-xl font-mono font-bold mt-1" 
                                data-date="{{ $product->getAttribute('special_end_date') }}">
                                Loading...
                            </div>
                        </div>
                    @endif

                    <form id="special-add-to-cart-form" method="POST" action="{{ route('shop.api.checkout.cart.store') }}">
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="quantity" value="1">
    
    <div class="mt-8">
        <button type="submit" class="primary-button w-full py-4 text-lg font-bold uppercase tracking-widest bg-black text-white hover:bg-gray-800 rounded">
            Add to Cart Now
        </button>
    </div>
</form>

{{-- Script Khusus untuk handle tombol Add to Cart layout spesial --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const specialForm = document.getElementById('special-add-to-cart-form');
        
        if(specialForm) {
            specialForm.addEventListener('submit', function(e) {
                e.preventDefault(); 
                
                const formData = new FormData(specialForm);
                const submitBtn = specialForm.querySelector('button');
                const originalText = submitBtn.innerText;
                
                // Ubah tombol jadi loading
                submitBtn.innerText = "Processing...";
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-75');

                // Kirim request ke API Bagisto
                window.axios.post("{{ route('shop.api.checkout.cart.store') }}", formData)
                    .then(function (response) {
                        // Jika ada Vue instance (Bagisto standar), update cart mini & notifikasi
                        if (window.app && window.app.__vue_app__) {
                            window.app.__vue_app__.config.globalProperties.$emitter.emit('update-mini-cart', response.data.data);
                            window.app.__vue_app__.config.globalProperties.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        } else {
                            // Fallback alert biasa
                            alert(response.data.message);
                            location.reload(); 
                        }
                    })
                    .catch(function (error) {
                        let errMsg = 'Something went wrong.';
                        if(error.response && error.response.data && error.response.data.message) {
                            errMsg = error.response.data.message;
                        }
                        alert(errMsg);
                    })
                    .finally(function() {
                        // Kembalikan tombol seperti semula
                        submitBtn.innerText = originalText;
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-75');
                    });
            });
        }
    });
</script>
                    
                    <div class="mt-6 text-gray-500 text-sm">
                        {!! $product->short_description !!}
                    </div>
                </div>
            </div>

            @if($product->getAttribute('special_promo_banner'))
                <div class="mt-20 w-full relative h-[400px] overflow-hidden rounded-xl group">
                    <img src="{{ Storage::url($product->getAttribute('special_banner_img')) }}" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
                    
                    <div class="absolute inset-0 bg-black/20 flex flex-col justify-center px-12">
                        <h2 class="text-white text-4xl font-serif font-bold mb-4 drop-shadow-md">
                            {{ $product->getAttribute('special_promo_title') ?? 'Exclusive Collection' }}
                        </h2>
                        <button class="bg-white text-black px-8 py-3 w-max font-bold hover:bg-gray-100 transition">
                            Explore More
                        </button>
                    </div>
                </div>
            @endif

            <div class="mt-20 mb-20">
                <h3 class="text-3xl font-serif mb-8 text-center">People Also Loved</h3>
                <div class="grid grid-cols-4 gap-6 max-md:grid-cols-2">
                    @foreach ($product->related_products as $related)
                         @include('shop::products.list.card', ['product' => $related])
                    @endforeach
                </div>
            </div>
            
        </div>

    @else
    
        {{-- ==================== TAMPILAN STANDAR (USER CODE) ==================== --}}
        {{-- Kode asli anda saya letakkan di dalam blok @else ini --}}
        
        <v-product>
            <x-shop::shimmer.products.view />
        </v-product>

        <div class="1180:mt-20">
            <div class="max-1180:hidden">
                <x-shop::tabs position="center" ref="productTabs">
                    {!! view_render_event('bagisto.shop.products.view.description.before', ['product' => $product]) !!}
                    <x-shop::tabs.item id="descritpion-tab" class="container mt-[60px] !p-0" :title="trans('shop::app.products.view.description')" :is-selected="true">
                        <div class="container mt-[60px] max-1180:px-5">
                            <p class="text-lg text-zinc-500 max-1180:text-sm">{!! $product->description !!}</p>
                        </div>
                    </x-shop::tabs.item>
                    {!! view_render_event('bagisto.shop.products.view.description.after', ['product' => $product]) !!}

                    @if(count($attributeData))
                        <x-shop::tabs.item id="information-tab" class="container mt-[60px] !p-0" :title="trans('shop::app.products.view.additional-information')" :is-selected="false">
                            <div class="container mt-[60px] max-1180:px-5">
                                <div class="mt-8 grid max-w-max grid-cols-[auto_1fr] gap-4">
                                    @foreach ($customAttributeValues as $customAttributeValue)
                                        @if (! empty($customAttributeValue['value']))
                                            <div class="grid"><p class="text-base text-black">{!! $customAttributeValue['label'] !!}</p></div>
                                            <div class="grid"><p class="text-base text-zinc-500">{!! $customAttributeValue['value'] !!}</p></div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </x-shop::tabs.item>
                    @endif

                    <x-shop::tabs.item id="review-tab" class="container mt-[60px] !p-0" :title="trans('shop::app.products.view.review')" :is-selected="false">
                        @include('shop::products.view.reviews')
                    </x-shop::tabs.item>
                </x-shop::tabs>
            </div>
        </div>

        <div class="container mt-6 grid gap-3 !p-0 max-1180:px-5 1180:hidden">
             <x-shop::accordion :is-active="true">
                 <x-slot:header class="bg-gray-100"><p class="font-medium">@lang('shop::app.products.view.description')</p></x-slot>
                 <x-slot:content>{!! $product->description !!}</x-slot>
             </x-shop::accordion>
             
             <x-shop::accordion :is-active="false">
                 <x-slot:header class="bg-gray-100"><p class="font-medium">@lang('shop::app.products.view.review')</p></x-slot>
                 <x-slot:content>@include('shop::products.view.reviews')</x-slot>
             </x-shop::accordion>
        </div>

        <v-product-associations />

    @endif
    
    {{-- ================================================================= --}}
    {{-- END LOGIC SWITCH --}}
    {{-- ================================================================= --}}

    {!! view_render_event('bagisto.shop.products.view.after', ['product' => $product]) !!}

    {!! view_render_event('bagisto.shop.products.view.after', ['product' => $product]) !!}

    @pushOnce('scripts')
        {{-- Template Vue untuk Logic Bagisto Standar --}}
        <script type="text/x-template" id="v-product-template">
            <x-shop::form v-slot="{ meta, errors, handleSubmit }" as="div">
                <form ref="formData" @submit="handleSubmit($event, addToCart)">
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="is_buy_now" v-model="is_buy_now">
                    
                    <div class="container px-[60px] max-1180:px-0">
                        <div class="mt-12 flex gap-9 max-1180:flex-wrap max-lg:mt-0 max-sm:gap-y-4">
                            @include('shop::products.view.gallery')

                            <div class="relative max-w-[590px] max-1180:w-full max-1180:max-w-full max-1180:px-5 max-sm:px-4">
                                {!! view_render_event('bagisto.shop.products.name.before', ['product' => $product]) !!}
                                <div class="flex justify-between gap-4">
                                    <h1 class="break-words text-3xl font-medium max-sm:text-xl">{{ $product->name }}</h1>
                                    @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                                        <div
                                            class="flex max-h-[46px] min-h-[46px] min-w-[46px] cursor-pointer items-center justify-center rounded-full border bg-white text-2xl transition-all hover:opacity-[0.8] max-sm:max-h-7 max-sm:min-h-7 max-sm:min-w-7 max-sm:text-base"
                                            role="button"
                                            aria-label="@lang('shop::app.products.view.add-to-wishlist')"
                                            tabindex="0"
                                            :class="isWishlist ? 'icon-heart-fill text-red-600' : 'icon-heart'"
                                            @click="addToWishlist"
                                        >
                                        </div>
                                    @endif
                                </div>
                                {!! view_render_event('bagisto.shop.products.name.after', ['product' => $product]) !!}

                                <div class="mt-1 w-max cursor-pointer">
                                    <x-shop::products.ratings :average="$avgRatings" :total="$reviewHelper->getTotalFeedback($product)" />
                                </div>

                                <p class="mt-[22px] flex items-center gap-2.5 text-2xl !font-medium">
                                    {!! $product->getTypeInstance()->getPriceHtml() !!}
                                </p>

                                <div class="mt-6 text-lg text-zinc-500">
                                    {!! $product->short_description !!}
                                </div>

                                @include('shop::products.view.types.simple')
                                @include('shop::products.view.types.configurable')
                                @include('shop::products.view.types.grouped')
                                @include('shop::products.view.types.bundle')
                                @include('shop::products.view.types.downloadable')
                                @include('shop::products.view.types.booking')

                                <div class="mt-8 flex max-w-[470px] gap-4 max-sm:mt-4">
                                    <x-shop::quantity-changer name="quantity" value="1" class="gap-x-4 rounded-xl px-7 py-4" />
                                    <x-shop::button type="submit" class="secondary-button w-full" :title="trans('shop::app.products.view.add-to-cart')" :disabled="! $product->isSaleable(1)" ::loading="isStoring.addToCart" @click="is_buy_now=0;" />
                                </div>
                                
                                <div class="mt-10 flex gap-9">
                                     <div class="flex cursor-pointer items-center justify-center gap-2.5 max-sm:gap-1.5 max-sm:text-base" role="button" @click="is_buy_now=0; addToCompare({{ $product->id }})">
                                        @if (core()->getConfigData('catalog.products.settings.compare_option'))
                                            <span class="icon-compare text-2xl" role="presentation"></span>
                                            @lang('shop::app.products.view.compare')
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </x-shop::form>
        </script>

        {{-- Script Vue JS App Component --}}
        <script type="module">
            app.component('v-product', {
                template: '#v-product-template',
                data() {
                    return {
                        isWishlist: Boolean("{{ (boolean) auth()->guard()->user()?->wishlist_items->where('channel_id', core()->getCurrentChannel()->id)->where('product_id', $product->id)->count() }}"),
                        isCustomer: '{{ auth()->guard('customer')->check() }}',
                        is_buy_now: 0,
                        isStoring: { addToCart: false, buyNow: false },
                    }
                },
                methods: {
                    addToCart(params) {
                        const operation = this.is_buy_now ? 'buyNow' : 'addToCart';
                        this.isStoring[operation] = true;
                        let formData = new FormData(this.$refs.formData);
                        this.ensureQuantity(formData);
                        
                        this.$axios.post('{{ route("shop.api.checkout.cart.store") }}', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
                            .then(response => {
                                if (response.data.message) {
                                    this.$emitter.emit('update-mini-cart', response.data.data);
                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                    if (response.data.redirect) window.location.href= response.data.redirect;
                                } else {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: response.data.data.message });
                                }
                                this.isStoring[operation] = false;
                            })
                            .catch(error => {
                                this.isStoring[operation] = false;
                                this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.message });
                            });
                    },
                    addToWishlist() {
                        if (this.isCustomer) {
                            this.$axios.post('{{ route('shop.api.customers.account.wishlist.store') }}', { product_id: "{{ $product->id }}" })
                                .then(response => {
                                    this.isWishlist = ! this.isWishlist;
                                    this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                                })
                                .catch(error => {});
                        } else {
                            window.location.href = "{{ route('shop.customer.session.index')}}";
                        }
                    },
                    addToCompare(productId) {
                        this.$axios.post('{{ route("shop.api.compare.store") }}', { product_id: productId })
                            .then(response => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    },
                    ensureQuantity(formData) { if (! formData.has('quantity')) formData.append('quantity', 1); },
                },
            });
        </script>
    @endPushOnce

</x-shop::layouts>
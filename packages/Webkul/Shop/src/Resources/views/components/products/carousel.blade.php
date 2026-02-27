<v-products-carousel
    src="{{ $src }}"
    title="{{ $title }}"
    navigation-link="{{ $navigationLink ?? '' }}"
>
    <x-shop::shimmer.products.carousel :navigation-link="$navigationLink ?? false" />
@pushOnce('styles')
    <style>
        /* --- CSS Grid Produk --- */
        .custom-product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }
        @media (max-width: 1024px) {
            .custom-product-grid { grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        }
        @media (max-width: 768px) {
            .custom-product-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        }

        /* --- CSS Judul ala New Arrivals --- */
        .na-header {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .na-title {
            font-size: 48px;
            font-weight: 700;
            color: #000033;
            font-family: 'Playfair Display', serif;
            margin: 0;
            line-height: 1;
            z-index: 2;
            position: relative;
        }
        .na-underline {
            display: block;
            width: 250px; /* Diperlebar sedikit agar muat untuk kata "New Products" */
            height: 18px;
            background-color: #FFF9C4;
            margin-top: -12px;
            border-radius: 50%;
            transform: rotate(-2deg);
            z-index: 1;
        }
        
        /* Penyesuaian Judul di HP */
        @media (max-width: 768px) {
            .na-title { font-size: 32px; }
            .na-underline { width: 170px; height: 14px; margin-top: -8px; }
        }
    </style>
@endPushOnce
</v-products-carousel>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-products-carousel-template"
    >
        <div
            class="container mt-20 max-lg:px-8 max-md:mt-8 max-sm:mt-7 max-sm:!px-4"
            v-if="! isLoading && products.length"
        >
            <div class="relative flex justify-center items-center mb-10 mt-5">
                <div class="na-header">
                    <h2 class="na-title max-md:text-4xl max-sm:text-3xl">
                        @{{ title }}
                    </h2>
                    <div class="na-underline"></div>
                </div>

                <div class="absolute right-0 top-1/2 -translate-y-1/2 hidden max-lg:flex" v-if="navigationLink">
                     <a :href="navigationLink" class="flex items-center gap-2 text-xl max-md:text-base">
                        @lang('shop::app.components.products.carousel.view-all')
                        <span class="icon-arrow-right text-2xl"></span>
                    </a>
                </div>
            </div>

            <div
                ref="swiperContainer"
                class="custom-product-grid mt-10 max-md:mt-5"
            >
                <x-shop::products.card
                    class="w-full max-md:h-fit"
                    v-for="product in products.slice(0, 8)"
                />
            </div>

            <a
                :href="navigationLink"
                class="secondary-button mx-auto mt-5 block w-max rounded-2xl px-11 py-3 text-center text-base max-lg:mt-0 max-lg:hidden max-lg:py-3.5 max-md:rounded-lg"
                :aria-label="title"
                v-if="navigationLink"
            >
                @lang('shop::app.components.products.carousel.view-all')
            </a>
        </div>

        <!-- Product Card Listing -->
        <template v-if="isLoading">
            <x-shop::shimmer.products.carousel :navigation-link="$navigationLink ?? false" />
        </template>
    </script>

    <script type="module">
        app.component('v-products-carousel', {
            template: '#v-products-carousel-template',

            props: [
                'src',
                'title',
                'navigationLink',
            ],

            data() {
                return {
                    isLoading: true,

                    products: [],

                    offset: 323,

                    isScreenMax2xl: window.innerWidth <= 1440,
                };
            },

            mounted() {
                this.getProducts();
            },

            created() {
                window.addEventListener('resize', this.updateScreenSize);
            },

            beforeDestroy() {
                window.removeEventListener('resize', this.updateScreenSize);
            },

            methods: {
                getProducts() {
                    this.$axios.get(this.src + (this.src.includes('?') ? '&' : '?') + 'limit=8')
                        .then(response => {
                            this.isLoading = false;

                            this.products = response.data.data;
                        }).catch(error => {
                            console.log(error);
                        });
                },

                updateScreenSize() {
                    this.isScreenMax2xl = window.innerWidth <= 1440;
                },

                swipeLeft() {
                    const container = this.$refs.swiperContainer;

                    container.scrollLeft -= this.offset;
                },

                swipeRight() {
                    const container = this.$refs.swiperContainer;

                    // Check if scroll reaches the end
                    if (container.scrollLeft + container.clientWidth >= container.scrollWidth) {
                        // Reset scroll to the beginning
                        container.scrollLeft = 0;
                    } else {
                        // Scroll to the right
                        container.scrollLeft += this.offset;
                    }
                },
            },
        });
    </script>
@endPushOnce

<div class="w-[418px] max-w-full max-md:w-full">
    {!! view_render_event('bagisto.shop.checkout.cart.summary.title.before') !!}

    <p
        class="text-2xl font-medium max-md:text-base"
        role="heading"
        aria-level="1"
    >
        @lang('shop::app.checkout.cart.summary.cart-summary')
    </p>

    {!! view_render_event('bagisto.shop.checkout.cart.summary.title.after') !!}

    <v-shipping-estimator 
        :cart="cart" 
        @shipping-selected="updateSummaryDisplay"
    ></v-shipping-estimator>

    <div class="mt-6 grid gap-4 max-md:mt-2 max-md:gap-2.5">

        <div class="flex justify-between text-right">
            <p class="text-base max-sm:text-sm">@lang('shop::app.checkout.cart.summary.sub-total')</p>
            <p class="text-base font-medium max-sm:text-sm">@{{ cart.formatted_sub_total }}</p>
        </div>

        <div 
            class="flex justify-between text-right"
            v-if="cart.discount_amount && parseFloat(cart.discount_amount) > 0"
        >
            <p class="text-base max-sm:text-sm">@lang('shop::app.checkout.cart.summary.discount-amount')</p>
            <p class="text-base font-medium max-sm:text-sm">@{{ cart.formatted_discount_amount }}</p>
        </div>

        @include('shop::checkout.coupon')

        <div class="flex justify-between text-right">
            <p class="text-base max-sm:text-sm">@lang('shop::app.checkout.cart.summary.delivery-charges')</p>
            
            <p class="text-base font-medium max-sm:text-sm text-blue-600">
                <span v-if="customShippingPrice !== null">@{{ customShippingFormatted }}</span>
                <span v-else>@{{ cart.formatted_shipping_amount }}</span>
            </p>
        </div>

        <div class="flex justify-between text-right" v-if="cart.tax_total > 0">
            <p class="text-base max-sm:text-sm">@lang('shop::app.checkout.cart.summary.tax')</p>
            <p class="text-base font-medium max-sm:text-sm">@{{ cart.formatted_tax_total }}</p>
        </div>

        <div class="flex justify-between text-right border-t pt-4">
            <p class="text-lg font-semibold max-md:text-base">@lang('shop::app.checkout.cart.summary.grand-total')</p>
            
            <p class="text-lg font-semibold max-md:text-base">
                <span v-if="customGrandTotalFormatted">@{{ customGrandTotalFormatted }}</span>
                <span v-else>@{{ cart.formatted_grand_total }}</span>
            </p>
        </div>

        <a
            href="{{ route('shop.checkout.onepage.index') }}"
            class="primary-button mt-4 place-self-end rounded-2xl px-11 py-3 max-md:my-4 max-md:max-w-full max-md:rounded-lg max-md:py-3 max-md:text-sm max-sm:w-full max-sm:py-2"
        >
            @lang('shop::app.checkout.cart.summary.proceed-to-checkout')
        </a>
    </div>
</div>

@pushOnce('scripts')
<script type="text/x-template" id="v-shipping-estimator-template">
        <div class="border rounded-lg p-4 bg-gray-50 mb-4">
            <p class="font-medium text-gray-800 mb-3 text-lg">Cek Ongkos Kirim</p>
            
            <div class="mb-3">
                <label class="block text-sm text-gray-600 mb-1">Negara</label>
                <select v-model="address.country" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white">
                    <option value="ID">Indonesia</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="block text-sm text-gray-600 mb-1">Provinsi</label>
                <select 
                    v-model="address.state" 
                    @change="handleProvinceChange" 
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white"
                >
                    <option value="" disabled>Pilih Provinsi</option>
                    <option v-for="prov in provinces" :key="prov.code" :value="prov.name">
                        @{{ prov.name }}
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label class="block text-sm text-gray-600 mb-1">Kota / Kabupaten</label>
                <select 
                    v-model="address.city" 
                    :disabled="cities.length === 0"
                    @change="calculateShipping" 
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-white disabled:bg-gray-100"
                >
                    <option value="" disabled>@{{ isFetchingCities ? 'Memuat...' : 'Pilih Kota' }}</option>
                    <option v-for="city in cities" :key="city.code" :value="city.name">
                        @{{ city.name }}
                    </option>
                </select>
            </div>

<div class="mb-3">
                 <label class="block text-sm text-gray-600 mb-1">Kode Pos</label>
                 <input 
                    type="text" 
                    v-model="address.postcode" 
                    @input="handlePostcodeInput"
                    maxlength="5"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" 
                    placeholder="Contoh: 40123"
                 >
                 </div>

            <div v-if="isLoading" class="text-center text-sm text-gray-500 py-2">
                <span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900 mr-2"></span>
                Menghitung ongkir...
            </div>

            <p v-if="errorMessage" class="text-red-500 text-xs mb-2">@{{ errorMessage }}</p>

            <div v-if="shippingMethods.length > 0" class="border-t pt-3 mt-2">
                <label class="block text-sm font-bold text-blue-700 mb-2">Pilih Layanan Pengiriman:</label>
                
                <select 
                    v-model="selectedRate" 
                    @change="applyShipping"
                    class="w-full rounded-md border-2 border-blue-500 bg-blue-50 px-3 py-2 text-sm font-medium focus:ring-blue-500"
                >
                    <option :value="null">-- Pilih Ekspedisi --</option>
                    <optgroup v-for="method in shippingMethods" :label="method.carrier_title">
                        <option v-for="rate in method.rates" :value="rate">
                            @{{ rate.method_title }} - @{{ formatPrice(rate.price) }}
                        </option>
                    </optgroup>
                </select>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-shipping-estimator', {
            template: '#v-shipping-estimator-template',
            props: ['cart'],
            emits: ['shipping-selected'], // Event ke parent
            
            data() {
                return {
                    address: {
                        country: 'ID',
                        state: '',
                        city: '',
                        postcode: ''
                    },
                    provinces: [],
                    cities: [],
                    isFetchingCities: false,
                    isLoading: false,
                    shippingMethods: [],
                    selectedRate: null,
                    errorMessage: ''
                }
            },

            created() {
                this.fetchProvinces();
            },

            methods: {
                // --- API LOGIC (Sama seperti Checkout) ---
                async fetchProvinces() {
                    try {
                        const response = await this.$axios.get('/indo-region/provinces');
                        this.provinces = response.data;
                    } catch (e) {}
                },

                async fetchCities(provinceCode) {
                    this.isFetchingCities = true;
                    this.cities = [];
                    this.address.city = '';
                    try {
                        const response = await this.$axios.get('/indo-region/cities/' + provinceCode);
                        this.cities = response.data;
                    } catch (e) {
                    } finally {
                        this.isFetchingCities = false;
                    }
                },

                    handleProvinceChange(event) {
                        const selectedName = event.target.value;
                        const selectedProvObj = this.provinces.find(p => p.name === selectedName);
                        
                        // Reset Ongkir Lama & Pilihan Kota saat ganti Provinsi
                        this.shippingMethods = [];
                        this.selectedRate = null;
                        this.$emit('shipping-selected', null);
                        
                        if (selectedProvObj) this.fetchCities(selectedProvObj.code);
                    },
                    handlePostcodeInput() {
                    // 1. Pastikan hanya angka (Hapus huruf jika ada yang iseng ngetik huruf)
                    this.address.postcode = this.address.postcode.replace(/[^0-9]/g, '');

                    // 2. Cek apakah panjangnya sudah pas 5 digit?
                    if (this.address.postcode.length === 5) {
                        // Jika sudah 5, langsung tembak API!
                        this.calculateShipping();
                    }
                },

                // --- HITUNG ONGKIR LOGIC ---
                calculateShipping() {
                    // Validasi: Jangan hitung jika Kota belum dipilih
                    if (!this.address.city) return;

                    this.isLoading = true;
                    this.errorMessage = '';
                    this.shippingMethods = [];
                    this.selectedRate = null;
                    
                    this.$emit('shipping-selected', null);

                    this.$axios.post("{{ route('shop.api.checkout.cart.estimate_shipping') }}", this.address)
                        .then(response => {
                            this.isLoading = false;
                            const result = response.data.data;
                            
                            if (result.shipping_methods) {
                                this.shippingMethods = result.shipping_methods;
                            } else {
                                this.shippingMethods = [];
                            }
                        })
                        .catch(error => {
                            this.isLoading = false;
                            // Silent error atau tampilkan pesan kecil jika perlu
                            // this.errorMessage = error.response?.data?.message; 
                        });
                },
                applyShipping() {
                    // Kirim data tarif yang dipilih ke Parent (Summary)
                    if (this.selectedRate) {
                        this.$emit('shipping-selected', this.selectedRate);
                    }
                },

                formatPrice(price) {
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
                }
            }
        });
    </script>

    <script type="module">
        // Kita tidak bisa mengedit v-cart langsung dari sini tanpa build.
        // TAPI, kita bisa memanipulasi tampilan summary menggunakan event listener Vue.
        // Di sini kita gunakan trik: Component Estimator di atas emit event, 
        // Component Parent (Summary HTML) menangkapnya lewat variabel global sementara atau mixin.
        
        // Agar lebih bersih, kita tambahkan method 'updateSummaryDisplay' di root app mixin 
        // atau kita handle manual di template v-cart jika memungkinkan.
        
        // SOLUSI: Karena kita tidak bisa ubah parent v-cart, 
        // Kita pasang logic penerima event langsung di tag <v-shipping-estimator>
        // Lalu kita simpan data 'customShippingPrice' di komponen v-cart LEWAT PROPERTI TAMBAHAN (Monkey Patch)
        // ATAU kita gunakan event bus sederhana.
    </script>
@endPushOnce
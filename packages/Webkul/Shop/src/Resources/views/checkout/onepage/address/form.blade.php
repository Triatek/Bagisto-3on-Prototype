@pushOnce('scripts')
    <script type="text/x-template" id="v-checkout-address-form-template">
        <div class="mt-2 max-md:mt-3">
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.id'"
                    ::value="address.id"
                />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.company_name.after') !!}

            <x-shop::form.control-group class="!mb-4">
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.full-name')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    name="fullname_display"
                    v-model="fullNameInput"
                    @input="handleFullNameChange"
                    rules="required"
                    :label="trans('shop::app.checkout.onepage.address.full-name')"
                    :placeholder="trans('shop::app.checkout.onepage.address.full-name-placeholder')"
                />
                
                <x-shop::form.control-group.error ::name="controlName + '.first_name'" />
            </x-shop::form.control-group>

            <div class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.first_name'"
                    v-model="address.first_name"
                    rules="required" 
                />
                
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.last_name'"
                    v-model="address.last_name"
                />
            </div>

            <x-shop::form.control-group class="!mb-4">
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.email')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="email"
                    ::name="controlName + '.email'"
                    ::value="address.email"
                    v-model="address.email"
                    rules="required|email"
                    :label="trans('shop::app.checkout.onepage.address.email')"
                    placeholder="email@example.com"
                    ::disabled="{{ auth('customer')->check() ? 'true' : 'false' }}"
                />

                <x-shop::form.control-group.error ::name="controlName + '.email'" />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.email.after') !!}

            <template v-if="controlName=='billing'">
                {!! view_render_event('bagisto.shop.checkout.onepage.address.form.vat_id.after') !!}
            </template>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.street-address')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.address.[0]'"
                    ::value="address.address[0]"
                    rules="required|address"
                    :label="trans('shop::app.checkout.onepage.address.street-address')"
                    :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                />

                <x-shop::form.control-group.error
                    class="mb-2"
                    ::name="controlName + '.address.[0]'"
                />

                @if (core()->getConfigData('customer.address.information.street_lines') > 1)
                    @for ($i = 1; $i < core()->getConfigData('customer.address.information.street_lines'); $i++)
                        <x-shop::form.control-group.control
                            type="text"
                            ::name="controlName + '.address.[{{ $i }}]'"
                            rules="address"
                            :label="trans('shop::app.checkout.onepage.address.street-address')"
                            :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                        />

                        <x-shop::form.control-group.error
                            class="mb-2"
                            ::name="controlName + '.address.[{{ $i }}]'"
                        />
                    @endfor
                @endif
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.address.after') !!}

            <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                <x-shop::form.control-group class="!mb-4">
                    <x-shop::form.control-group.label class="{{ core()->isCountryRequired() ? 'required' : '' }} !mt-0">
                        @lang('shop::app.checkout.onepage.address.country')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="select"
                        ::name="controlName + '.country'"
                        ::value="address.country"
                        v-model="selectedCountry"
                        rules="{{ core()->isCountryRequired() ? 'required' : '' }}"
                        :label="trans('shop::app.checkout.onepage.address.country')"
                        :placeholder="trans('shop::app.checkout.onepage.address.country')"
                    >
                        <option value="">
                            @lang('shop::app.checkout.onepage.address.select-country')
                        </option>

                        <option
                            v-for="country in countries"
                            :value="country.code"
                        >
                            @{{ country.name }}
                        </option>
                    </x-shop::form.control-group.control>

                    <x-shop::form.control-group.error ::name="controlName + '.country'" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.checkout.onepage.address.form.country.after') !!}

                <x-shop::form.control-group class="!mb-4">
                    <x-shop::form.control-group.label class="required !mt-0">
                        @lang('shop::app.checkout.onepage.address.state')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="select"
                        ::name="controlName + '.state'"
                        ::value="address.state"
                        v-model="address.state"
                        rules="required"
                        :label="trans('shop::app.checkout.onepage.address.state')"
                        @change="handleProvinceChange"
                    >
                        <option value="" disabled>@lang('shop::app.checkout.onepage.address.select-province')</option>
                        <option v-for="prov in provinces" :key="prov.code" :value="prov.name">
                            @{{ prov.name }}
                        </option>
                    </x-shop::form.control-group.control>
    
                    <x-shop::form.control-group.error ::name="controlName + '.state'" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.checkout.onepage.address.form.state.after') !!}
            </div>

            <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                <x-shop::form.control-group class="!mb-4">
                    <x-shop::form.control-group.label class="required !mt-0">
                        @lang('shop::app.checkout.onepage.address.city')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="select"
                        ::name="controlName + '.city'"
                        ::value="address.city"
                        v-model="address.city"
                        rules="required"
                        :label="trans('shop::app.checkout.onepage.address.city')"
                        ::disabled="cities.length === 0"
                    >
                        <option value="" disabled>@{{ isFetchingCities ? '@lang('shop::app.checkout.onepage.address.loading')' : '@lang('shop::app.checkout.onepage.address.select-city')' }}</option>
                        <option v-for="city in cities" :key="city.code" :value="city.name">
                            @{{ city.name }}
                        </option>
                    </x-shop::form.control-group.control>
    
                    <x-shop::form.control-group.error ::name="controlName + '.city'" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.checkout.onepage.address.form.city.after') !!}

                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="{{ core()->isPostCodeRequired() ? 'required' : '' }} !mt-0">
                        @lang('shop::app.checkout.onepage.address.postcode')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        ::name="controlName + '.postcode'"
                        ::value="address.postcode"
                        rules="{{ core()->isPostCodeRequired() ? 'required' : '' }}|postcode"
                        :label="trans('shop::app.checkout.onepage.address.postcode')"
                        :placeholder="trans('shop::app.checkout.onepage.address.postcode')"
                    />

                    <x-shop::form.control-group.error ::name="controlName + '.postcode'" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.checkout.onepage.address.form.postcode.after') !!}
            </div>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.telephone')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.phone'"
                    ::value="address.phone"
                    rules="required|phone"
                    :label="trans('shop::app.checkout.onepage.address.telephone')"
                    :placeholder="trans('shop::app.checkout.onepage.address.telephone')"
                />

                <x-shop::form.control-group.error ::name="controlName + '.phone'" />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.phone.after') !!}
        </div>
    </script>

    <script type="module">
        app.component('v-checkout-address-form', {
            template: '#v-checkout-address-form-template',

            props: {
                controlName: {
                    type: String,
                    required: true,
                },

                address: {
                    type: Object,
                    default: () => ({
                        id: 0,
                        company_name: '',
                        first_name: '',
                        last_name: '',
                        email: '',
                        address: [],
                        country: '',
                        state: '',
                        city: '',
                        postcode: '',
                        phone: '',
                    }),
                },
            },

            data() {
                return {
                    template: '#v-checkout-address-form-template',
                    countries: [],
                    states: [],
                    fullNameInput: '', 
                    provinces: [],
                    cities: [],
                    isFetchingCities: false,
                }
            },

            created() {
                // 1. Fetch Provinces
                this.fetchProvinces();

                // 2. Auto-Fill Logic
                let profileFirstName = "{{ auth('customer')->user()?->first_name ?? '' }}";
                let profileLastName  = "{{ auth('customer')->user()?->last_name ?? '' }}";
                let profileEmail     = "{{ auth('customer')->user()?->email ?? '' }}";

                // A. Logic Mengisi Nama (Saat load pertama)
                if (this.address.first_name) {
                    // Jika sedang edit alamat
                    this.fullNameInput = this.address.first_name;
                    if (this.address.last_name) {
                        this.fullNameInput += ' ' + this.address.last_name;
                    }
                } 
                else {
                    // Jika alamat baru (ambil dari profil)
                    if (profileFirstName) {
                        this.fullNameInput = profileFirstName;
                        this.address.first_name = profileFirstName;
                        
                        if (profileLastName) {
                            this.fullNameInput += ' ' + profileLastName;
                            this.address.last_name = profileLastName;
                        } else {
                            this.address.last_name = ''; // Profil cuma 1 kata, last_name kosong
                        }
                    }
                }

                if (!this.address.email && profileEmail) {
                    this.address.email = profileEmail;
                }
                
                if (!this.address.country) {
                    this.address.country = 'ID';
                }
            },

            computed: {
                haveStates() {
                    return !! this.states[this.selectedCountry]?.length;
                },
            },

            mounted() {
                this.getCountries();
                this.getStates();
            },

            methods: {
                getCountries() {
                    this.$axios.get("{{ route('shop.api.core.countries') }}")
                        .then(response => {
                            this.countries = response.data.data;
                        })
                        .catch(() => {});
                },

                getStates() {
                    this.$axios.get("{{ route('shop.api.core.states') }}")
                        .then(response => {
                            this.states = response.data.data;
                        })
                        .catch(() => {});
                },

                // --- LOGIC BARU: Last Name jadi NULL/KOSONG jika 1 kata ---
                handleFullNameChange() {
                    let rawInput = this.fullNameInput;
                    let cleanName = rawInput.trim(); 
                    
                    if (!cleanName) {
                        this.address.first_name = '';
                        this.address.last_name = '';
                        return;
                    }

                    let spaceIndex = cleanName.indexOf(' ');

                    if (spaceIndex === -1) {
                        // KASUS: Hanya 1 kata
                        this.address.first_name = cleanName;
                        this.address.last_name = ''; // REQUEST: Last Name dikosongkan
                    } else {
                        // KASUS: Lebih dari 1 kata
                        this.address.first_name = cleanName.substring(0, spaceIndex);
                        this.address.last_name = cleanName.substring(spaceIndex + 1);
                    }
                },

                async fetchProvinces() {
                    try {
                        const response = await this.$axios.get("{{ route('api.provinces') }}");
                        this.provinces = response.data;
                    } catch (error) {
                        console.error('Failed to fetch provinces:', error);
                    }
                },

                async fetchCities(provinceCode) {
                    this.isFetchingCities = true;
                    this.cities = [];
                    this.address.city = ''; 
                    
                    try {
                        const response = await this.$axios.get("{{ url('/indo-region/cities') }}/" + provinceCode);
                        this.cities = response.data;
                    } catch (error) {
                        console.error('Failed to fetch cities:', error);
                    } finally {
                        this.isFetchingCities = false;
                    }
                },

                handleProvinceChange(event) {
                    const selectedName = event.target.value;
                    const selectedProvObj = this.provinces.find(p => p.name === selectedName);

                    if (selectedProvObj) {
                        this.fetchCities(selectedProvObj.code);
                    }
                }
            }
        });
    </script>
@endPushOnce
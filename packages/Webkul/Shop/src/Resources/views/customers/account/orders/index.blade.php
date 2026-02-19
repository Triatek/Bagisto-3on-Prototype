<x-shop::layouts.account>
    <x-slot:title>
        @lang('shop::app.customers.account.orders.title')
    </x-slot>

    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="orders" />
        @endSection
    @endif

    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="mx-4 flex-auto max-md:mx-6 max-sm:mx-4">
        <div class="mb-8 flex items-center max-sm:mb-5">
            <a class="grid md:hidden" href="{{ route('shop.customers.account.index') }}">
                <span class="icon-arrow-left rtl:icon-arrow-right text-2xl"></span>
            </a>

            <h2 class="text-2xl font-medium max-sm:text-base ltr:ml-2.5 md:ltr:ml-0 rtl:mr-2.5 md:rtl:mr-0">
                @lang('shop::app.customers.account.orders.title')
            </h2>
        </div>

        {!! view_render_event('bagisto.shop.customers.account.orders.list.before') !!}

        <div class="max-md:hidden">
            <x-shop::datagrid :src="route('shop.customers.account.orders.index')">
                <template #header>
                    <div class="hidden"></div>
                </template>

                <template #body="{ available, columns }">
                    <div class="grid grid-cols-6 gap-4 px-4 py-3 border-b font-bold bg-gray-50 text-sm text-gray-700 rounded-t-lg">
                        <div class="col-span-1">Order ID</div>
                        <div class="col-span-1">Tanggal</div>
                        <div class="col-span-1">Total</div>
                        <div class="col-span-1">Status Order</div>
                        <div class="col-span-1 text-center">Pembayaran</div>
                        <div class="col-span-1 text-center">Aksi</div>
                    </div>

                    <div v-for="record in available.records" class="grid grid-cols-6 gap-4 px-4 py-4 border-b items-center text-sm hover:bg-gray-50 transition-colors">
                        <div class="col-span-1 font-semibold text-gray-800">#@{{ record.id }}</div>
                        <div class="col-span-1 text-gray-600">@{{ record.created_at }}</div>
                        <div class="col-span-1 font-bold text-gray-900">@{{ record.grand_total }}</div>
                        <div class="col-span-1" v-html="record.status"></div>

                        <div class="col-span-1 text-center">
                            <a v-if="record.status.toLowerCase().includes('pending')"
                               :href="'{{ route('shop.customers.orders.pay_now', '') }}/' + record.id"
                               target="_blank"
                               class="inline-block bg-[#0071bc] hover:bg-[#005a96] text-black text-xs font-bold py-2 px-4 rounded shadow-sm transition-all duration-200"
                            >
                                Bayar Sekarang
                            </a>

                            <span v-else-if="record.status.toLowerCase().includes('processing') || record.status.toLowerCase().includes('completed')" 
                                  class="text-green-600 font-bold text-xs bg-green-100 px-2 py-1 rounded">
                                Lunas
                            </span>
                        </div>
                        
                        <div class="col-span-1 flex justify-center">
                            <a :href="'{{ route('shop.customers.account.orders.view', '') }}/' + record.id">
                                <span class="icon-eye text-2xl text-gray-500 hover:text-black cursor-pointer transition-colors"></span>
                            </a>
                        </div>
                    </div>
                </template>
            </x-shop::datagrid>
        </div>

        <div class="md:hidden">
            <x-shop::datagrid :src="route('shop.customers.account.orders.index')">
                <template #header><div class="hidden"></div></template>
                <template #body="{ available, isLoading }">
                    <template v-if="isLoading">
                        <x-shop::shimmer.datagrid.table.body />
                    </template>
    
                    <template v-else>
                        <div v-for="record in available.records" class="w-full p-4 border rounded-lg mb-4 bg-white shadow-sm">
                            <div class="flex justify-between items-start mb-3 border-b pb-2">
                                <div>
                                    <p class="text-sm font-bold text-gray-800">Order #@{{ record.id }}</p>
                                    <p class="text-xs text-gray-500">@{{ record.created_at }}</p>
                                </div>
                                <div v-html="record.status"></div>
                            </div>

                            <div class="flex justify-between items-center mb-4">
                                <span class="text-xs text-gray-500">Total Tagihan</span>
                                <p class="text-lg font-bold text-gray-900">@{{ record.grand_total }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <a :href="'{{ route('shop.customers.account.orders.view', '') }}/' + record.id"
                                   class="flex items-center justify-center border border-gray-300 text-gray-700 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition">
                                    <span class="icon-eye text-lg mr-2"></span> Detail
                                </a>

                                <a v-if="record.status.toLowerCase().includes('pending')"
                                   :href="'{{ route('shop.customers.orders.pay_now', '') }}/' + record.id"
                                   target="_blank"
                                   class="flex items-center justify-center bg-[#0071bc] text-black py-2 rounded-md text-sm font-bold hover:bg-[#005a96] shadow-sm transition">
                                    Bayar Sekarang
                                </a>
                            </div>
                        </div>
                    </template>
                </template>
            </x-shop::datagrid>
        </div>
    
        {!! view_render_event('bagisto.shop.customers.account.orders.list.after') !!}
    </div>
</x-shop::layouts.account>
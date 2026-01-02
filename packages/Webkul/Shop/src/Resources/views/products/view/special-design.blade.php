<div class="container mx-auto py-10">

    <div class="flex flex-wrap">
        <div class="w-full md:w-1/2">
             @include('shop::products.view.gallery') 
        </div>

        <div class="w-full md:w-1/2 px-5">
            <h1 class="text-4xl font-bold">{{ $product->name }}</h1>
            
            <div class="text-2xl text-red-600 font-bold mt-2">
                {{ core()->currency($product->getTypeInstance()->getMinimalPrice()) }}
            </div>

            @if($product->getAttribute('special_end_date'))
                <div class="bg-red-100 text-red-600 p-3 mt-4 rounded">
                    Sale ends on: {{ $product->getAttribute('special_end_date') }}
                </div>
            @endif

            <div class="mt-8">
                <v-product-card-details :product-id="{{ $product->id }}"></v-product-card-details>
                <form method="POST" action="{{ route('shop.cart.add', $product->id) }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" class="btn btn-primary btn-lg">Add To Cart</button>
                </form>
            </div>
        </div>
    </div>

    @if($product->getAttribute('special_banner_img'))
        <div class="mt-20 w-full relative">
            <img src="{{ Storage::url($product->getAttribute('special_banner_img')) }}" class="w-full h-auto object-cover">
            
            <div class="absolute inset-0 flex items-center justify-center">
                <h2 class="text-white text-5xl font-bold drop-shadow-md">
                    {{ $product->name }} Collection
                </h2>
            </div>
        </div>
    @endif

    <div class="mt-20">
        <h3 class="text-2xl font-bold mb-5">People Also Loved</h3>
        <div class="grid grid-cols-4 gap-4">
            @foreach ($product->related_products as $related)
                @include('shop::products.list.card', ['product' => $related])
            @endforeach
        </div>
    </div>

</div>
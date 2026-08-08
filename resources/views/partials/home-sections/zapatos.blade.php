@if(isset($products) && $products->count() > 0)
<section class="mb-4">
    <div class="container">
        <div class="px-2 py-4 px-md-4 py-md-3 bg-white shadow-sm rounded">
            <div class="d-flex mb-3 align-items-baseline border-bottom">
                <h3 class="h5 fw-700 mb-0">
                    <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">
                        <i class="las la-shoe-prints mr-1"></i> Zapatos
                    </span>
                </h3>
                @if(isset($category))
                <a href="{{ route('category.show', $category->slug) }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">
                    View All
                </a>
                @endif
            </div>
            <div class="aiz-carousel gutters-10 half-outside-arrow"
                 data-items="6" data-xl-items="5" data-lg-items="4"
                 data-md-items="3" data-sm-items="2" data-xs-items="2" data-arrows="true">
                @foreach($products as $product)
                <div class="carousel-box">
                    @include('partials.product-card', ['product' => $product])
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

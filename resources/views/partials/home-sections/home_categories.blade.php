@if(isset($categories) && $categories->count() > 0)
<section class="mb-4">
    <div class="container">
        <div class="px-2 py-4 px-md-4 py-md-3 bg-white shadow-sm rounded">
            <div class="d-flex mb-3 align-items-baseline border-bottom">
                <h3 class="h5 fw-700 mb-0">
                    <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">Shop by Category</span>
                </h3>
            </div>
            <div class="row gutters-10">
                @foreach($categories as $category)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('category.show', $category->slug) }}"
                       class="d-block text-center p-3 bg-soft-primary rounded mb-3 text-reset">
                        <img src="{{ asset('storage/' . $category->banner) }}"
                             alt="{{ $category->name }}"
                             class="img-fluid mb-2"
                             style="height:60px; object-fit:contain;"
                             onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}'">
                        <div class="fs-13 fw-600">{{ $category->name }}</div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
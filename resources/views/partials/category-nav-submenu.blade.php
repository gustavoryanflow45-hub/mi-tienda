{{-- resources/views/partials/category-nav-submenu.blade.php --}}
<div class="p-3">
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <h4 class="fs-16 fw-700 mb-0">{{ $category->name }}</h4>
        <a href="{{ route('category.show', $category->slug) }}" class="fs-12 text-primary">Ver todo ></a>
    </div>

    @if($subCategories->count() > 0)
    <div class="row gutters-10">
        @foreach($subCategories as $sub)
        <div class="col-6 col-md-4 col-lg-3 mb-3">
            <a href="{{ route('category.show', $sub->slug) }}" class="d-block text-center text-reset">
                <img
                    src="{{ uploaded_asset($sub->icon) }}"
                    alt="{{ $sub->name }}"
                    class="mb-1"
                    width="40"
                    height="40"
                    style="object-fit:contain;"
                    onerror="this.onerror=null;this.src='{{ asset('assets/img/placeholder.jpg') }}';"
                >
                <div class="fs-12 text-truncate">{{ $sub->name }}</div>
            </a>
        </div>
        @endforeach
    </div>
    @else
    <p class="fs-13 opacity-60 mb-0">No hay subcategorías disponibles.</p>
    @endif
</div>

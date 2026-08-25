{{-- resources/views/partials/header.blade.php --}}
<header class="sticky-top z-1020 bg-white border-bottom shadow-sm">
    <div class="position-relative logo-bar-area z-1">
        <div class="container">
            <div class="d-flex align-items-center">

                {{-- LOGO --}}
                <div class="col-auto col-xl-3 pl-0 pr-3 d-flex align-items-center">
                    <a class="d-block py-20px mr-3 ml-0" href="{{ route('home') }}">
                        <img src="{{ asset('assets/img/logo.png') }}" alt="Woot" class="mw-100 h-30px h-md-40px" height="40">
                    </a>
                </div>

                {{-- Ícono búsqueda móvil --}}
                <div class="d-lg-none ml-auto mr-0">
                    <a class="p-2 d-block text-reset" href="javascript:void(0);" data-toggle="class-toggle" data-target=".front-header-search">
                        <i class="las la-search la-flip-horizontal la-2x"></i>
                    </a>
                </div>

                {{-- BARRA DE BÚSQUEDA --}}
                <div class="flex-grow-1 front-header-search d-flex align-items-center bg-white">
                    <div class="position-relative flex-grow-1">
                        <form action="{{ route('search') }}" method="GET" class="stop-propagation">
                            <div class="d-flex position-relative align-items-center">
                                <div class="d-lg-none" data-toggle="class-toggle" data-target=".front-header-search">
                                    <button class="btn px-2" type="button">
                                        <i class="la la-2x la-long-arrow-left"></i>
                                    </button>
                                </div>
                                <div class="input-group">
                                    <input type="text" class="border-0 border-lg form-control" id="search" name="keyword"
                                           placeholder="I am shopping for..." autocomplete="off"
                                           value="{{ request('keyword') }}">
                                    <div class="input-group-append d-none d-lg-block">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="la la-search la-flip-horizontal fs-18"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        {{-- Resultados de búsqueda en tiempo real --}}
                        <div class="typed-search-box stop-propagation document-click-d-none d-none bg-white rounded shadow-lg position-absolute left-0 top-100 w-100" style="min-height: 200px">
                            <div class="search-preloader absolute-top-center">
                                <div class="dot-loader"><div></div><div></div><div></div></div>
                            </div>
                            <div class="search-nothing d-none p-3 text-center fs-16"></div>
                            <div id="search-content" class="text-left"></div>
                        </div>
                    </div>
                </div>

                {{-- COMPARAR --}}
                <div class="d-none d-lg-block ml-3 mr-0">
                    <div id="compare"></div>
                </div>

                {{-- WISHLIST --}}
                <div class="d-none d-lg-block ml-3 mr-0">
                    <div id="wishlist"></div>
                </div>

                {{-- CARRITO --}}
              <div class="d-none d-lg-block align-self-stretch ml-3 mr-0" data-hover="dropdown">
             <div class="nav-cart-box dropdown h-100" id="cart_items">
        <a href="javascript:void(0)" class="d-flex align-items-center text-reset h-100" data-toggle="dropdown" data-display="static">
            <i class="la la-shopping-cart la-2x opacity-80"></i>
            <span class="flex-grow-1 ml-1">
                <span class="badge badge-primary badge-inline badge-pill cart-count">0</span>
                <span class="nav-box-text d-none d-xl-block opacity-70">Cart</span>
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg p-0 stop-propagation" id="nav-cart-dropdown">
            {{-- Se carga dinámicamente con AJAX --}}
            <div class="text-center p-3">
                <i class="las la-spinner la-spin la-2x opacity-60"></i>
            </div>
        </div>
    </div>
</div>

            </div>
        </div>
    </div>

    {{-- NAVBAR DE CATEGORÍAS --}}
    <div class="bg-white border-top border-gray-200 py-1">
        <div class="container">
            <ul class="list-inline mb-0 pl-0 mobile-hor-swipe text-center">
                <li class="list-inline-item mr-0">
                    <a href="{{ route('home') }}" class="opacity-60 fs-14 px-3 py-2 d-inline-block fw-600 hov-opacity-100 text-reset {{ request()->routeIs('home') ? 'text-primary opacity-100' : '' }}">
                        Home
                    </a>
                </li>
                <li class="list-inline-item mr-0">
                    <a href="{{ route('products.index') }}" class="opacity-60 fs-14 px-3 py-2 d-inline-block fw-600 hov-opacity-100 text-reset {{ request()->routeIs('products.*') ? 'text-primary opacity-100' : '' }}">
                        Product
                    </a>
                </li>
                <li class="list-inline-item mr-0">
                    <a href="{{ route('orders.index') }}" class="opacity-60 fs-14 px-3 py-2 d-inline-block fw-600 hov-opacity-100 text-reset {{ request()->routeIs('orders.*') ? 'text-primary opacity-100' : '' }}">
                        Order
                    </a>
                </li>
                <li class="list-inline-item mr-0">
                    <a href="{{ route('wallet.index') }}" class="opacity-60 fs-14 px-3 py-2 d-inline-block fw-600 hov-opacity-100 text-reset {{ request()->routeIs('wallet.*') ? 'text-primary opacity-100' : '' }}">
                        Wallet
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

{{-- NAVEGACIÓN MÓVIL INFERIOR --}}
<div class="aiz-mobile-bottom-nav d-xl-none fixed-bottom bg-white shadow-lg border-top rounded-top"
     style="box-shadow: 0px -1px 10px rgb(0 0 0 / 15%)!important;">
    <div class="row align-items-center gutters-5">
        <div class="col">
            <a href="{{ route('home') }}" class="text-reset d-block text-center pb-2 pt-3">
                <i class="las la-home fs-20 {{ request()->routeIs('home') ? 'text-primary opacity-100' : 'opacity-60' }}"></i>
                <span class="d-block fs-10 fw-600 {{ request()->routeIs('home') ? 'text-primary opacity-100' : 'opacity-60' }}">Home</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('categories.index') }}" class="text-reset d-block text-center pb-2 pt-3">
                <i class="las la-list-ul fs-20 opacity-60"></i>
                <span class="d-block fs-10 fw-600 opacity-60">Categories</span>
            </a>
        </div>
        <div class="col-auto">
            <a href="{{ route('cart.index') }}" class="text-reset d-block text-center pb-2 pt-3">
                <span class="align-items-center bg-primary border border-white border-width-4 d-flex justify-content-center position-relative rounded-circle size-50px"
                      style="margin-top: -33px; box-shadow: 0px -5px 10px rgb(0 0 0 / 15%); border-color: #fff !important;">
                    <i class="las la-shopping-bag la-2x text-white"></i>
                </span>
                <span class="d-block mt-1 fs-10 fw-600 opacity-60">
                    Cart (<span class="cart-count">0</span>)
                </span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('login') }}" class="text-reset d-block text-center pb-2 pt-3">
                <span class="d-inline-block position-relative px-2">
                    <i class="las la-bell fs-20 opacity-60"></i>
                </span>
                <span class="d-block fs-10 fw-600 opacity-60">Notifications</span>
            </a>
        </div>
        <div class="col">
            @auth
                <a href="{{ route('profile') }}" class="text-reset d-block text-center pb-2 pt-3">
            @else
                <a href="{{ route('login') }}" class="text-reset d-block text-center pb-2 pt-3">
            @endauth
                <span class="d-block mx-auto">
                    <img src="{{ asset('assets/img/avatar-place.png') }}" class="rounded-circle size-20px">
                </span>
                <span class="d-block fs-10 fw-600 opacity-60">Account</span>
            </a>
        </div>
    </div>
</div>

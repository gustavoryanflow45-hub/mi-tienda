<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Woot') | {{ config('app.name', 'Woot') }}</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="description" content="@yield('meta_description', '')">
    <meta name="keywords" content="@yield('meta_keywords', '')">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/img/favicon.png') }}">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&display=swap" rel="stylesheet">

    <!-- CSS Files -->
    <link rel="stylesheet" href="{{ asset('assets/css/vendors.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/aiz-core.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom-style.css') }}">

    @yield('extra_css')

    <script>
        var AIZ = AIZ || {};
        AIZ.local = {
            nothing_selected: 'Nothing selected',
            nothing_found: 'Nothing found',
            choose_file: 'Choose File',
            file_selected: 'File selected',
            files_selected: 'Files selected',
            add_more_files: 'Add more files',
            adding_more_files: 'Adding more files',
            drop_files_here_paste_or: 'Drop files here, paste or',
            browse: 'Browse',
            upload_complete: 'Upload complete',
            upload_paused: 'Upload paused',
            resume_upload: 'Resume upload',
            pause_upload: 'Pause upload',
            retry_upload: 'Retry upload',
            cancel_upload: 'Cancel upload',
            uploading: 'Uploading',
            processing: 'Processing',
            complete: 'Complete',
            file: 'File',
            files: 'Files',
        }
    </script>

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            font-weight: 400;
        }
        :root {
            --primary: #679941;
            --hov-primary: #679941;
            --soft-primary: rgba(103, 153, 65, 0.15);
        }
        #map, #edit_map {
            width: 100%;
            height: 250px;
        }
        .pac-container { z-index: 100000; }
    </style>
</head>
<body>
    <div class="aiz-main-wrapper d-flex flex-column">

        {{-- TOP BANNER --}}
        @include('partials.top-banner')

        {{-- TOP BAR (idioma, login) --}}
        @include('partials.topbar')

        {{-- HEADER (logo, búsqueda, carrito) --}}
        @include('partials.header')

        {{-- Modales globales --}}
        <div class="modal fade" id="order_details" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                <div class="modal-content">
                    <div id="order-details-modal-body"></div>
                </div>
            </div>
        </div>

        {{-- Mensajes flash (p. ej. tienda pendiente de aprobación) --}}
        @include('partials.flash')

        {{-- CONTENIDO DE CADA PÁGINA --}}
        @yield('content')

        {{-- FOOTER --}}
        @include('partials.footer')

    </div>

    {{-- Modal eliminar confirmación --}}
    <script>
        function confirm_modal(delete_url) {
            jQuery('#confirm-delete').modal('show', {backdrop: 'static'});
            document.getElementById('delete_link').setAttribute('href', delete_url);
        }
    </script>
    <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmation</h4>
                </div>
                <div class="modal-body">
                    <p>Delete confirmation message</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <a id="delete_link" class="btn btn-danger btn-ok">Delete</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Añadir al carrito --}}
    <div class="modal fade" id="addToCart">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-zoom product-modal" id="modal-size" role="document">
            <div class="modal-content position-relative">
                <div class="c-preloader text-center p-3">
                    <i class="las la-spinner la-spin la-3x"></i>
                </div>
                <button type="button" class="close absolute-top-right btn-icon close z-1" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="la-2x">&times;</span>
                </button>
                <div id="addToCart-modal-body"></div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="{{ asset('assets/js/vendors.js') }}"></script>
    <script src="{{ asset('assets/js/aiz-core.js') }}"></script>

    <script>
        $(document).ready(function () {
            // Cambio de idioma
            if ($('#lang-change').length > 0) {
                $('#lang-change .dropdown-menu a').each(function () {
                    $(this).on('click', function (e) {
                        e.preventDefault();
                        var locale = $(this).data('flag');
                        $.post('{{ route("language.change") }}', {
                            _token: AIZ.data.csrf,
                            locale: locale
                        }, function () { location.reload(); });
                    });
                });
            }

            // Cambio de moneda
            if ($('#currency-change').length > 0) {
                $('#currency-change .dropdown-menu a').each(function () {
                    $(this).on('click', function (e) {
                        e.preventDefault();
                        var currency_code = $(this).data('currency');
                        $.post('{{ route("currency.change") }}', {
                            _token: AIZ.data.csrf,
                            currency_code: currency_code
                        }, function () { location.reload(); });
                    });
                });
            }

            // Búsqueda en tiempo real
            $('#search').on('keyup focus', function () {
                var searchKey = $(this).val();
                if (searchKey.length > 0) {
                    $('body').addClass('typed-search-box-shown');
                    $('.typed-search-box').removeClass('d-none');
                    $('.search-preloader').removeClass('d-none');
                    $.post('{{ route("search.ajax") }}', {
                        _token: AIZ.data.csrf,
                        search: searchKey
                    }, function (data) {
                        if (data == '0') {
                            $('#search-content').html(null);
                            $('.typed-search-box .search-nothing').removeClass('d-none').html('Sorry, nothing found for <strong>"' + searchKey + '"</strong>');
                        } else {
                            $('.typed-search-box .search-nothing').addClass('d-none').html(null);
                            $('#search-content').html(data);
                        }
                        $('.search-preloader').addClass('d-none');
                    });
                } else {
                    $('.typed-search-box').addClass('d-none');
                    $('body').removeClass('typed-search-box-shown');
                }
            });
        });

        // Cargar mini-carrito
        function loadMiniCart() {
    @auth
    fetch('{{ route("cart.mini") }}')
        .then(r => r.json())
        .then(data => {
            document.querySelectorAll('.cart-count').forEach(el => el.textContent = data.count);
            document.getElementById('nav-cart-dropdown').innerHTML = data.html;
        });
    @endauth
}

        function updateNavCart(count) {
    document.querySelectorAll('.cart-count').forEach(el => el.textContent = count);
    loadMiniCart();
}

        function miniCartRemove(cartId) {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    fetch('{{ route("cart.remove") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ cart_id: cartId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            updateNavCart(data.cart_count);
        }
    });
}

// Cargar al iniciar la página
document.addEventListener('DOMContentLoaded', loadMiniCart);

// Recargar al abrir el dropdown
document.addEventListener('DOMContentLoaded', function() {
    const cartBox = document.querySelector('.nav-cart-box');
    if (cartBox) {
        cartBox.addEventListener('show.bs.dropdown', loadMiniCart);
        // Para Bootstrap 4 (que usa jQuery)
        $(cartBox).on('show.bs.dropdown', loadMiniCart);
    }
});

        function addToCompare(id) {
            $.post('{{ route("compare.add") }}', {
                _token: AIZ.data.csrf,
                id: id
            }, function (data) {
                $('#compare').html(data);
                AIZ.plugins.notify('success', 'Item has been added to compare list');
            });
        }

        function addToWishList(id) {
            @auth
            $.post('{{ route("wishlist.add") }}', {
                _token: AIZ.data.csrf,
                id: id
            }, function (data) {
                AIZ.plugins.notify('success', 'Item added to wishlist');
            });
            @else
            AIZ.plugins.notify('warning', 'Please login first');
            @endauth
        }

        function showAddToCartModal(id) {
            if (!$('#modal-size').hasClass('modal-lg')) {
                $('#modal-size').addClass('modal-lg');
            }
            $('#addToCart-modal-body').html(null);
            $('#addToCart').modal();
            $('.c-preloader').show();
            $.post('{{ route("cart.modal") }}', {
                _token: AIZ.data.csrf,
                id: id
            }, function (data) {
                $('.c-preloader').hide();
                $('#addToCart-modal-body').html(data);
                AIZ.plugins.slickCarousel();
                AIZ.plugins.zoom();
                AIZ.extra.plusMinus();
                getVariantPrice();
            });
        }

        function getVariantPrice() {
            if ($('#option-choice-form input[name=quantity]').val() > 0 && checkAddToCartValidity()) {
                $.ajax({
                    type: 'POST',
                    url: '{{ route("product.variant_price") }}',
                    data: $('#option-choice-form').serializeArray(),
                    success: function (data) {
                        $('#option-choice-form #chosen_price_div').removeClass('d-none');
                        $('#option-choice-form #chosen_price_div #chosen_price').html(data.price);
                        $('#available-quantity').html(data.quantity);
                        $('.input-number').prop('max', data.max_limit);
                        if (parseInt(data.in_stock) == 0 && data.digital == 0) {
                            $('.buy-now, .add-to-cart').addClass('d-none');
                            $('.out-of-stock').removeClass('d-none');
                        } else {
                            $('.buy-now, .add-to-cart').removeClass('d-none');
                            $('.out-of-stock').addClass('d-none');
                        }
                    }
                });
            }
        }

        function checkAddToCartValidity() {
            var names = {};
            $('#option-choice-form input:radio').each(function () {
                names[$(this).attr('name')] = true;
            });
            var count = 0;
            $.each(names, function () { count++; });
            return $('#option-choice-form input:radio:checked').length == count;
        }

        function addToCart() {
            if (checkAddToCartValidity()) {
                $('#addToCart').modal();
                $('.c-preloader').show();
                $.ajax({
                    type: 'POST',
                    url: '{{ route("cart.add") }}',
                    data: $('#option-choice-form').serializeArray(),
                    success: function (data) {
                        $('#addToCart-modal-body').html(null);
                        $('.c-preloader').hide();
                        $('#modal-size').removeClass('modal-lg');
                        $('#addToCart-modal-body').html(data.modal_view);
                        AIZ.extra.plusMinus();
                        AIZ.plugins.slickCarousel();
                        updateNavCart(data.nav_cart_view, data.cart_count);
                    }
                });
            } else {
                AIZ.plugins.notify('warning', 'Please choose all the options');
            }
        }
    </script>

    @yield('extra_js')
</body>
</html>

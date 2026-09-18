<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- aiz-core.js lee estos dos al arrancar (AIZ.data). No los quites:
         AIZ.extra.trimAppUrl() hace appUrl.slice() sin comprobar, así que
         sin el meta lanza y corta el resto del init: carruseles, tooltips,
         contadores y zoom se quedan sin inicializar. --}}
    <meta name="app-url" content="{{ url('/') }}">
    <meta name="file-base-url" content="{{ url('/') }}/public/">

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

        // ── Modal rápido de añadir al carrito (tarjeta de producto) ──
        //
        // El modal sirve partials/quick-add-modal.blade.php ya renderizado.
        // Antes pedía JSON y lo inyectaba con .html(), de modo que volcaba el
        // JSON crudo en pantalla: desde la rejilla no se podía elegir talla ni
        // color, solo desde la ficha completa.
        //
        // El comportamiento se queda aquí, delegado sobre #qa-root, para no
        // reinyectar los mismos scripts en cada apertura.

        function showAddToCartModal(id) {
            // CartController entero pide 'auth': para un invitado la petición
            // redirige al login y el modal acabaría mostrando esa página.
            @guest
            AIZ.plugins.notify('warning', 'Inicia sesión para añadir productos al carrito');
            setTimeout(function () { window.location.href = '{{ url("/login") }}'; }, 1200);
            return;
            @endguest

            $('#addToCart-modal-body').html(null);
            $('#addToCart').modal();
            $('.c-preloader').show();

            $.post('{{ route("cart.modal") }}', {
                _token: AIZ.data.csrf,
                id: id
            }, function (html) {
                $('.c-preloader').hide();
                $('#addToCart-modal-body').html(html);
                quickAddRefresh();
            }).fail(function () {
                $('.c-preloader').hide();
                AIZ.plugins.notify('danger', 'No se pudo cargar el producto');
            });
        }

        function quickAddData() {
            var root = document.getElementById('qa-root');
            if (!root) return null;

            return {
                root: root,
                productId: parseInt(root.dataset.productId, 10),
                hasSizes: root.dataset.hasSizes === '1',
                hasColors: root.dataset.hasColors === '1',
                totalStock: parseInt(root.dataset.totalStock, 10) || 0,
                map: JSON.parse(root.dataset.stockMap || '{}')
            };
        }

        function quickAddSelection(root) {
            var size  = root.querySelector('input[name="qa-size"]:checked');
            var color = root.querySelector('input[name="qa-color"]:checked');

            return { size: size ? size.value : null, color: color ? color.value : null };
        }

        // Misma clave que arma ProductStock::buildVariant(): talla primero.
        function quickAddKey(size, color) {
            return [size, color].filter(Boolean).join('-');
        }

        /** true si queda alguna combinación con stock que incluya este valor. */
        function quickAddReachable(d, dimension, value) {
            var sel   = quickAddSelection(d.root);
            // Al evaluar una dimensión se ignora lo ya elegido en ella misma,
            // o elegir una talla dejaría todas las demás en agotado.
            var size  = dimension === 'size'  ? value : sel.size;
            var color = dimension === 'color' ? value : sel.color;

            if ((d.hasSizes && !size) || (d.hasColors && !color)) {
                return Object.keys(d.map).some(function (key) {
                    return d.map[key].qty > 0 && key.split('-').indexOf(value) !== -1;
                });
            }

            var entry = d.map[quickAddKey(size, color)];
            return !!entry && entry.qty > 0;
        }

        function quickAddRefresh() {
            var d = quickAddData();
            if (!d) return;

            var sel = quickAddSelection(d.root);

            d.root.querySelectorAll('.qa-size').forEach(function (chip) {
                chip.classList.toggle('selected', chip.dataset.size === sel.size);
                chip.classList.toggle('no-stock', !quickAddReachable(d, 'size', chip.dataset.size));
            });

            d.root.querySelectorAll('.qa-color').forEach(function (chip) {
                chip.classList.toggle('selected', chip.dataset.color === sel.color);
                chip.classList.toggle('no-stock', !quickAddReachable(d, 'color', chip.dataset.color));
            });

            var qty   = d.root.querySelector('#qa-qty');
            var stock = d.root.querySelector('#qa-stock');
            var price = d.root.querySelector('#qa-price-value');
            var full  = (!d.hasSizes || sel.size) && (!d.hasColors || sel.color);

            if (!full) {
                if (stock) stock.textContent = '(' + d.totalStock + ' disponibles)';
                if (qty) qty.max = d.totalStock;
                return;
            }

            var entry = d.map[quickAddKey(sel.size, sel.color)];

            if (!entry) {
                if (stock) stock.textContent = '(combinación no disponible)';
                if (qty) { qty.max = 0; qty.value = 1; }
                return;
            }

            if (price) price.textContent = '$' + entry.price.toFixed(2);
            if (stock) stock.textContent = '(' + entry.qty + ' disponibles)';
            if (qty) {
                qty.max = entry.qty;
                if (parseInt(qty.value, 10) > entry.qty) qty.value = Math.max(1, entry.qty);
            }
        }

        /** Qué falta por elegir; null si la selección ya es válida. */
        function quickAddMissing(d) {
            var sel = quickAddSelection(d.root);

            if (d.hasSizes && !sel.size)   return 'Elige una talla antes de continuar.';
            if (d.hasColors && !sel.color) return 'Elige un color antes de continuar.';

            if (d.hasSizes || d.hasColors) {
                var entry = d.map[quickAddKey(sel.size, sel.color)];
                if (!entry)         return 'Esa combinación no está disponible.';
                if (entry.qty <= 0) return 'Esa combinación está agotada.';
            }

            return null;
        }

        function quickAddSubmit() {
            @guest
            AIZ.plugins.notify('warning', 'Inicia sesión para añadir productos al carrito');
            setTimeout(function () { window.location.href = '{{ url("/login") }}'; }, 1200);
            return;
            @endguest

            var d = quickAddData();
            if (!d) return;

            // El servidor rechaza una combinación incompleta igualmente; se
            // avisa aquí para ahorrar el viaje y señalar qué falta.
            var missing = quickAddMissing(d);
            if (missing) {
                AIZ.plugins.notify('warning', missing);
                return;
            }

            var sel = quickAddSelection(d.root);
            var btn = d.root.querySelector('.qa-add');
            var original = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="las la-spinner la-spin"></i> Añadiendo...';

            $.ajax({
                type: 'POST',
                url: '{{ route("cart.add") }}',
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': AIZ.data.csrf },
                data: JSON.stringify({
                    product_id: d.productId,
                    quantity: parseInt(d.root.querySelector('#qa-qty').value, 10) || 1,
                    variation: quickAddKey(sel.size, sel.color) || null
                })
            }).done(function (res) {
                if (res.status === 'success') {
                    updateNavCart(res.cart_count);
                    $('#addToCart').modal('hide');
                    AIZ.plugins.notify('success', 'Producto añadido al carrito');
                } else {
                    AIZ.plugins.notify('warning', res.message || 'No se pudo añadir al carrito');
                }
            }).fail(function () {
                AIZ.plugins.notify('danger', 'Error de red, inténtalo de nuevo');
            }).always(function () {
                btn.disabled = false;
                btn.innerHTML = original;
            });
        }

        // Delegado: el contenido del modal se reemplaza en cada apertura.
        $(document).on('change', '#qa-root input[name="qa-size"], #qa-root input[name="qa-color"]', quickAddRefresh);

        $(document).on('click', '#qa-root .qa-qty-btn', function () {
            var input = document.getElementById('qa-qty');
            var max   = parseInt(input.max, 10) || 1;
            var next  = (parseInt(input.value, 10) || 1) + parseInt(this.dataset.step, 10);

            input.value = Math.min(Math.max(1, next), Math.max(1, max));
        });

        $(document).on('click', '#qa-root .qa-add', quickAddSubmit);
    </script>

    @yield('extra_js')
</body>
</html>

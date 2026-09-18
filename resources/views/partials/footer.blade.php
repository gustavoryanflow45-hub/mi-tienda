{{-- resources/views/partials/footer.blade.php --}}

{{-- Sección de políticas --}}
<section class="bg-white border-top mt-auto">
    <div class="container">
        <div class="row no-gutters">
            <div class="col-lg-3 col-md-6">
                <a class="text-reset border-left text-center p-4 d-block" href="{{ route('terms') }}">
                    <i class="la la-file-text la-3x text-primary mb-2"></i>
                    <h4 class="h6">{{ __('Términos y condiciones') }}</h4>
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a class="text-reset border-left text-center p-4 d-block" href="{{ route('return-policy') }}">
                    <i class="la la-mail-reply la-3x text-primary mb-2"></i>
                    <h4 class="h6">{{ __('Política de devoluciones') }}</h4>
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a class="text-reset border-left text-center p-4 d-block" href="{{ route('support-policy') }}">
                    <i class="la la-support la-3x text-primary mb-2"></i>
                    <h4 class="h6">{{ __('Política de soporte') }}</h4>
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a class="text-reset border-left border-right text-center p-4 d-block" href="{{ route('privacy-policy') }}">
                    <i class="las la-exclamation-circle la-3x text-primary mb-2"></i>
                    <h4 class="h6">{{ __('Política de privacidad') }}</h4>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Footer principal --}}
<section class="bg-dark py-5 text-light footer-widget">
    <div class="container">
        <div class="row">
            {{-- Logo y newsletter --}}
            <div class="col-lg-5 col-xl-4 text-center text-md-left">
                <div class="mt-4">
                    <a href="{{ route('home') }}" class="d-block">
                        <img class="lazyload" src="{{ asset('assets/img/placeholder-rect.jpg') }}"
                             data-src="{{ asset('assets/img/logo-footer.png') }}"
                             alt="Woot" height="44">
                    </a>
                    <div class="d-inline-block d-md-block mb-4 mt-3">
                        <form class="form-inline" method="POST" action="{{ route('subscribers.store') }}">
                            @csrf
                            <div class="form-group mb-0">
                                <input type="email" class="form-control" placeholder="{{ __('Tu correo electrónico') }}" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Suscribirme') }}</button>
                        </form>
                    </div>
                    <div class="w-300px mw-100 mx-auto mx-md-0">
                        <a href="#" target="_blank" class="d-inline-block mr-3 ml-0">
                            <img src="{{ asset('assets/img/play.png') }}" class="mx-100 h-40px">
                        </a>
                        <a href="#" target="_blank" class="d-inline-block">
                            <img src="{{ asset('assets/img/app.png') }}" class="mx-100 h-40px">
                        </a>
                    </div>
                </div>
            </div>

            {{-- Contacto --}}
            <div class="col-lg-3 ml-xl-auto col-md-4 mr-0">
                <div class="text-center text-md-left mt-4">
                    <h4 class="fs-13 text-uppercase fw-600 border-bottom border-gray-900 pb-2 mb-4">{{ __('Contacto') }}</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <span class="d-block opacity-30">{{ __('Dirección:') }}</span>
                            <span class="d-block opacity-70">Woot LLC 4121 International Parkway Carrollton, TX 75007</span>
                        </li>
                        <li class="mb-2">
                            <span class="d-block opacity-30">{{ __('Teléfono:') }}</span>
                            <span class="d-block opacity-70">214-445-2819</span>
                        </li>
                        <li class="mb-2">
                            <span class="d-block opacity-30">{{ __('Correo:') }}</span>
                            <span class="d-block opacity-70">
                                <a href="mailto:copyright@woot.com" class="text-reset">copyright@woot.com</a>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Servicio al cliente --}}
            <div class="col-lg-2 col-md-4">
                <div class="text-center text-md-left mt-4">
                    <h4 class="fs-13 text-uppercase fw-600 border-bottom border-gray-900 pb-2 mb-4">{{ __('Atención al cliente') }}</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="https://t.me/Woot_Hanna" class="opacity-50 hov-opacity-100 text-reset">
                                {{ __('Telegram · Atención al cliente 1') }}
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="https://t.me/Hanna_Woot" class="opacity-50 hov-opacity-100 text-reset">
                                {{ __('Telegram · Atención al cliente 2') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Mi cuenta --}}
            <div class="col-md-4 col-lg-2">
                <div class="text-center text-md-left mt-4">
                    <h4 class="fs-13 text-uppercase fw-600 border-bottom border-gray-900 pb-2 mb-4">{{ __('Mi cuenta') }}</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a class="opacity-50 hov-opacity-100 text-reset" href="{{ route('login') }}">{{ __('Iniciar sesión') }}</a>
                        </li>
                        <li class="mb-2">
                            <a class="opacity-50 hov-opacity-100 text-reset" href="{{ route('orders.index') }}">{{ __('Historial de pedidos') }}</a>
                        </li>
                        <li class="mb-2">
                            <a class="opacity-50 hov-opacity-100 text-reset" href="{{ route('wishlist.index') }}">{{ __('Mi lista de deseos') }}</a>
                        </li>
                        <li class="mb-2">
                            <a class="opacity-50 hov-opacity-100 text-reset" href="{{ route('track-order') }}">{{ __('Rastrear pedido') }}</a>
                        </li>
                        <li class="mb-2">
                            <a class="opacity-50 hov-opacity-100 text-light" href="{{ route('affiliate') }}">{{ __('Sé un afiliado') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="text-center text-md-left mt-4">
                    <h4 class="fs-13 text-uppercase fw-600 border-bottom border-gray-900 pb-2 mb-4">{{ __('Vende con nosotros') }}</h4>
                    <a href="{{ route('shops.create') }}" class="btn btn-primary btn-sm shadow-md">{{ __('Solicitar ahora') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Footer inferior --}}
<footer class="pt-3 pb-7 pb-xl-3 bg-black text-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-4">
                <div class="text-center text-md-left"></div>
            </div>
            <div class="col-lg-4">
                <ul class="list-inline my-3 my-md-0 social colored text-center">
                    <li class="list-inline-item">
                        <a href="https://www.facebook.com/" target="_blank" class="facebook">
                            <i class="lab la-facebook-f"></i>
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="https://twitter.com/" target="_blank" class="twitter">
                            <i class="lab la-twitter"></i>
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="https://www.instagram.com/" target="_blank" class="instagram">
                            <i class="lab la-instagram"></i>
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="https://www.youtube.com/" target="_blank" class="youtube">
                            <i class="lab la-youtube"></i>
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="https://www.linkedin.com/" target="_blank" class="linkedin">
                            <i class="lab la-linkedin-in"></i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-lg-4">
                <div class="text-center text-md-right">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item">
                            <img src="{{ asset('assets/img/payment-methods.png') }}" height="30" class="mw-100 h-auto" style="max-height: 30px">
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</footer>

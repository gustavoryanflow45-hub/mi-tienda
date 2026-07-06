{{-- resources/views/partials/topbar.blade.php --}}
<div class="top-navbar bg-white border-bottom border-soft-secondary z-1035">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 col">
                <ul class="list-inline d-flex justify-content-between justify-content-lg-start mb-0">
                    <li class="list-inline-item dropdown mr-3" id="lang-change">
                        <a href="javascript:void(0)" class="dropdown-toggle text-reset py-2" data-toggle="dropdown" data-display="static">
                            <img src="{{ asset('assets/img/placeholder.jpg') }}" data-src="https://flagcdn.com/160x120/ca.png" class="mr-2 lazyload" alt="English" height="11">
                            <span class="opacity-60">English</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-left">
                            <li>
                                <a href="javascript:void(0)" data-flag="en" class="dropdown-item">
                                    <img src="https://flagcdn.com/160x120/ca.png" class="mr-1 lazyload" alt="English" height="11">
                                    <span class="language">English</span>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" data-flag="es" class="dropdown-item">
                                    <img src="{{ asset('assets/img/placeholder.jpg') }}" data-src="https://flagcdn.com/160x120/es.png" class="mr-1 lazyload" alt="Español" height="11">
                                    <span class="language">Español</span>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" data-flag="fr" class="dropdown-item">
                                    <img src="{{ asset('assets/img/placeholder.jpg') }}" data-src="https://flagcdn.com/160x120/fr.png" class="mr-1 lazyload" alt="French" height="11">
                                    <span class="language">French</span>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" data-flag="de" class="dropdown-item">
                                    <img src="{{ asset('assets/img/placeholder.jpg') }}" data-src="https://flagcdn.com/160x120/de.png" class="mr-1 lazyload" alt="German" height="11">
                                    <span class="language">German</span>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" data-flag="pt" class="dropdown-item">
                                    <img src="{{ asset('assets/img/placeholder.jpg') }}" data-src="https://flagcdn.com/160x120/pt.png" class="mr-1 lazyload" alt="Portuguese" height="11">
                                    <span class="language">Portuguese</span>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)" data-flag="jp" class="dropdown-item">
                                    <img src="{{ asset('assets/img/placeholder.jpg') }}" data-src="https://flagcdn.com/160x120/jp.png" class="mr-1 lazyload" alt="Japanese" height="11">
                                    <span class="language">Japanese</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="col-5 text-right d-none d-lg-block">
                <ul class="list-inline mb-0 h-100 d-flex justify-content-end align-items-center">
                    @guest
                        <li class="list-inline-item mr-3 border-right border-left-0 pr-3 pl-0">
                            <a href="{{ route('login') }}" class="text-reset d-inline-block opacity-60 py-2">Login</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="{{ route('register') }}" class="text-reset d-inline-block opacity-60 py-2">Registration</a>
                        </li>
                    @else
                        <li class="list-inline-item mr-3">
                            <span class="opacity-60">{{ Auth::user()->name }}</span>
                        </li>
                        <li class="list-inline-item">
                            <a href="{{ route('logout') }}" class="text-reset d-inline-block opacity-60 py-2"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Cerrar Sesion</a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </div>
</div>
